<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A shipment: one or more physical packages, billed to one customer.
 *
 * The recipient is a snapshot of name and phone only. No address is stored —
 * different people may collect the same shipment, and the Beirut delivery
 * team takes the address at handover (D-019).
 */
class Shipment extends Model
{
    use HasFactory;

    /** Bytes of randomness behind the public tracking token. */
    private const TOKEN_BYTES = 24;

    protected $fillable = [
        'customer_id',
        'recipient_name',
        'recipient_phone',
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

    /** @return array<int, string> */
    private function activePackageStatuses(): array
    {
        return collect(\App\Enums\PackageStatus::cases())
            ->filter(fn ($status) => $status->isActive())
            ->map(fn ($status) => $status->value)
            ->values()
            ->all();
    }
}
