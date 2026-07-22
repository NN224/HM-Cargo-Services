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
        return ['رحلة' => 'batch'];
    }
}
