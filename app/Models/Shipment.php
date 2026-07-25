<?php

namespace App\Models;

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Models\Concerns\GuardsDeletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A shipment: one or more physical packages, billed to one customer.
 *
 * The recipient is a snapshot of name and phone only. No address is stored —
 * different people may collect the same shipment, and the Beirut delivery
 * team takes the address at handover (D-019).
 *
 * @property int $id
 * @property string $reference
 * @property string $public_token
 * @property int $customer_id
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property ShipmentStatus $status
 * @property numeric $total_weight_kg
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $intake_notified_at
 * @property \Illuminate\Support\Carbon|null $arrival_notified_at
 * @property int|null $batch_id
 * @property int|null $rate_per_kg_cents
 * @property int|null $computed_charge_cents
 * @property int|null $final_charge_cents
 * @property int $paid_amount_cents
 * @property string|null $priced_at
 * @property int|null $destination_warehouse_id
 * @property-read \App\Models\Batch|null $batch
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Warehouse|null $destinationWarehouse
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Package> $packages
 * @property-read int|null $packages_count
 * @method static Builder<static>|Shipment awaitingBatch()
 * @method static Builder<static>|Shipment newModelQuery()
 * @method static Builder<static>|Shipment newQuery()
 * @method static Builder<static>|Shipment query()
 * @method static Builder<static>|Shipment whereBatchId($value)
 * @method static Builder<static>|Shipment whereComputedChargeCents($value)
 * @method static Builder<static>|Shipment whereCreatedAt($value)
 * @method static Builder<static>|Shipment whereCustomerId($value)
 * @method static Builder<static>|Shipment whereDestinationWarehouseId($value)
 * @method static Builder<static>|Shipment whereFinalChargeCents($value)
 * @method static Builder<static>|Shipment whereId($value)
 * @method static Builder<static>|Shipment wherePaidAmountCents($value)
 * @method static Builder<static>|Shipment wherePricedAt($value)
 * @method static Builder<static>|Shipment wherePublicToken($value)
 * @method static Builder<static>|Shipment whereRatePerKgCents($value)
 * @method static Builder<static>|Shipment whereRecipientName($value)
 * @method static Builder<static>|Shipment whereRecipientPhone($value)
 * @method static Builder<static>|Shipment whereReference($value)
 * @method static Builder<static>|Shipment whereStatus($value)
 * @method static Builder<static>|Shipment whereTotalWeightKg($value)
 * @method static Builder<static>|Shipment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Shipment extends Model
{
    use GuardsDeletion, HasFactory;

    /** Bytes of randomness behind the public tracking token. */
    private const TOKEN_BYTES = 24;

    protected $fillable = [
        'customer_id',
        'recipient_name',
        'recipient_phone',
        'destination_warehouse_id',
        'status',
        'reference',
        'public_token',
        'intake_notified_at',
        'arrival_notified_at',
    ];

    protected $attributes = [
        'status' => ShipmentStatus::Pending->value,
        'total_weight_kg' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            // Cast as string, not float: a decimal that passes through a
            // float loses exactness, which is the bug this system must not
            // reproduce (D-007).
            'total_weight_kg' => 'decimal:4',
            'intake_notified_at' => 'datetime',
            'arrival_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $shipment): void {
            $shipment->reference ??= self::generateReference();
            $shipment->public_token ??= self::generatePublicToken();
        });
    }

    /**
     * A short, human reference staff can read aloud.
     *
     * Sequential and predictable by design — which is exactly why it must
     * never appear in a public tracking URL. That is the token's job.
     */
    private static function generateReference(): string
    {
        $year = now()->year;
        $sequence = static::whereYear('created_at', $year)->max('id') ?? 0;

        return sprintf('HM-%d-%06d', $year, $sequence + 1);
    }

    /**
     * The public tracking token: high entropy, unguessable, and unrelated to
     * any internal identifier (AGENTS.md).
     */
    private static function generatePublicToken(): string
    {
        do {
            $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        } while (static::where('public_token', $token)->exists());

        return $token;
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * Set what the customer actually pays (D-020).
     *
     * The operator types the final amount, never an adjustment — no mental
     * arithmetic on a difference. The computed charge is left untouched so the
     * two figures together always explain the total.
     */
    public function setFinalCharge(int $cents): void
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException(
                'المبلغ النهائي لا يمكن أن يكون سالباً. التصحيحات تُسجَّل كتسويات.'
            );
        }

        $this->forceFill(['final_charge_cents' => $cents])->save();
    }

    /**
     * The manual rounding, derived rather than stored.
     *
     * Negative means the customer was billed less than computed, positive
     * more. Deriving it means the adjustment can never disagree with the two
     * figures it sits between.
     */
    public function roundingAdjustmentCents(): int
    {
        return (int) $this->final_charge_cents - (int) $this->computed_charge_cents;
    }

    /** What remains unpaid on this shipment, in cents. */
    public function outstandingCents(): int
    {
        return max(0, (int) $this->final_charge_cents - (int) $this->paid_amount_cents);
    }

    /** @return HasMany<Package, $this> */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Recompute the total from the packages themselves.
     *
     * The sum is done by the database rather than in PHP so the decimal
     * columns are added exactly. Summing floats in PHP reintroduces the
     * 0.1 + 0.2 = 0.30000000000000004 error this system must never show a
     * customer.
     */
    public function recalculateTotalWeight(): void
    {
        $total = $this->packages()
            ->whereIn('status', $this->activePackageStatuses())
            ->sum(DB::raw('CAST(weight_kg AS DECIMAL(12,4))'));

        // Assigned directly rather than through update(). total_weight_kg is
        // derived from the packages and is deliberately absent from $fillable,
        // so mass assignment would silently discard it — which it did, leaving
        // the total stuck at zero with no error raised.
        $this->total_weight_kg = $total;
        $this->save();
    }

    /**
     * Derive the shipment stage from package facts after a scan.
     *
     * No batch action calls this to fabricate arrival. The only caller that
     * advances physical arrival is PackageScanService after it has written the
     * scanned package itself.
     */
    public function recalculateOperationalStatus(): void
    {
        $active = $this->packages()
            ->where('status', '!=', PackageStatus::Cancelled->value);
        $activeCount = (clone $active)->count();

        if ($activeCount === 0) {
            return;
        }

        $hasException = (clone $active)
            ->whereIn('status', [
                PackageStatus::Missing->value,
                PackageStatus::Damaged->value,
            ])
            ->exists();

        if ($hasException) {
            $this->forceFill(['status' => ShipmentStatus::Exception])->save();

            return;
        }

        $collectedCount = (clone $active)
            ->where('status', PackageStatus::Collected->value)
            ->count();

        if ($collectedCount === $activeCount) {
            $this->forceFill(['status' => ShipmentStatus::Collected])->save();

            return;
        }

        if ($collectedCount > 0) {
            $this->forceFill(['status' => ShipmentStatus::PartiallyCollected])->save();

            return;
        }

        $destinationCount = (clone $active)
            ->whereIn('status', [
                PackageStatus::ArrivedDestination->value,
                PackageStatus::Collected->value,
            ])
            ->count();

        if ($destinationCount === $activeCount) {
            $this->forceFill(['status' => ShipmentStatus::ReadyForCollection])->save();

            return;
        }

        if ($destinationCount > 0) {
            $this->forceFill(['status' => ShipmentStatus::PartialAtDestination])->save();

            return;
        }

        $transitCount = (clone $active)
            ->whereIn('status', [
                PackageStatus::ArrivedTransit->value,
                PackageStatus::DepartedTransit->value,
                PackageStatus::ArrivedDestination->value,
                PackageStatus::Collected->value,
            ])
            ->count();

        if ($transitCount === $activeCount) {
            $this->forceFill(['status' => ShipmentStatus::AtTransit])->save();
        } elseif ($transitCount > 0) {
            $this->forceFill(['status' => ShipmentStatus::PartialAtTransit])->save();
        }
    }

    /** @return array<int, string> */
    private function activePackageStatuses(): array
    {
        return collect(PackageStatus::cases())
            ->filter(fn ($status) => $status->isActive())
            ->map(fn ($status) => $status->value)
            ->values()
            ->all();
    }

    /**
     * Cargo received and waiting to be priced onto a batch.
     *
     * Has packages (real cargo, not an empty draft), no batch yet, and not
     * cancelled — a cancelled shipment is not waiting for anything, so counting
     * it would inflate a dashboard number that is supposed to mean "there is
     * work here". Centralised so the count and any future use share one
     * definition of the state.
     *
     * @param  Builder<Shipment>  $query
     */
    public function scopeAwaitingBatch(Builder $query): void
    {
        $query->whereNull('batch_id')
            ->whereHas('packages')
            ->where('status', '!=', ShipmentStatus::Cancelled->value);
    }

    /**
     * Packages are part of a shipment and are removed with it, so they do not
     * block. A batch does: once priced, the shipment is part of that batch's
     * revenue and profit.
     *
     * @return array<string, string>
     */
    protected function deletionDependencies(): array
    {
        return ['رحلات' => 'batch'];
    }

    public function markIntakeNotified(): void
    {
        $this->update(['intake_notified_at' => now()]);
    }

    public function markArrivalNotified(): void
    {
        $this->update(['arrival_notified_at' => now()]);
    }

    public function paidCents(): int
    {
        return (int) DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->where('payment_allocations.shipment_id', $this->id)
            ->where('payments.type', '!=', 'reversal')
            ->sum('payment_allocations.amount_cents');
    }

    public function isFullyPaid(): bool
    {
        if ($this->final_charge_cents === null || $this->final_charge_cents === 0) {
            return false;
        }

        return $this->paidCents() >= $this->final_charge_cents;
    }

    public function paymentStatusLabel(): string
    {
        if ($this->final_charge_cents === null || $this->final_charge_cents === 0) {
            return 'غير مسعر';
        }

        $paid = $this->paidCents();

        if ($paid >= $this->final_charge_cents) {
            return 'مدفوع بالكامل';
        }

        if ($paid > 0) {
            $remainingDollars = number_format(($this->final_charge_cents - $paid) / 100, 2);
            return "مدفوع جزئياً (متبقي $$remainingDollars)";
        }

        $totalDollars = number_format($this->final_charge_cents / 100, 2);
        return "غير مدفوع ($$totalDollars)";
    }
}
