<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One physical package inside a shipment.
 *
 * Every package carries a system-generated barcode and an exact decimal
 * weight (D-011). Weight is never rounded and there is no minimum billable
 * weight (D-007).
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'weight_kg',
        'status',
        'source_barcode',
        'description',
        'barcode',
    ];

    protected $attributes = [
        'status' => PackageStatus::Received->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => PackageStatus::class,
            'weight_kg' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $package): void {
            $package->barcode ??= self::generateBarcode();
        });

        static::saving(function (self $package): void {
            // Guarded here rather than only in a form, because AGENTS.md
            // requires invariants to hold regardless of the entry point.
            if ((float) $package->weight_kg <= 0) {
                throw new InvalidArgumentException(
                    'وزن الطرد يجب أن يكون أكبر من صفر.'
                );
            }
        });

        // Keep the shipment total in step with its packages no matter how they
        // change — Filament repeater, an import, or a console command. Relying
        // on the UI to call recalculate() would leave the total wrong the first
        // time a package is touched from anywhere else.
        $sync = fn (self $package) => $package->shipment?->recalculateTotalWeight();

        static::created($sync);
        static::updated($sync);
        static::deleted($sync);
    }

    /**
     * A unique, system-generated barcode.
     *
     * Deliberately not derived from a timestamp. The previous prototype used
     * PKG-{milliseconds}-{rand(0,999)}, which collides whenever two packages
     * are created within the same millisecond — a one-in-a-thousand chance on
     * every pair, and a certainty under bulk entry.
     */
    private static function generateBarcode(): string
    {
        do {
            $barcode = 'PKG-'.strtoupper(Str::random(10));
        } while (static::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /** @return BelongsTo<Shipment, $this> */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /** Whether this package still counts toward its shipment's obligations. */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /** Physical arrival at destination is established only by a package scan. */
    public function hasArrivedAtDestination(): bool
    {
        return in_array($this->status, [
            PackageStatus::ArrivedDestination,
            PackageStatus::Collected,
        ], true);
    }
}
