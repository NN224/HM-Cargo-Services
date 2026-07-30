<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One physical package inside a shipment.
 *
 * Every package carries a system-generated barcode and an exact decimal
 * weight (D-011). Weight is never rounded and there is no minimum billable
 * weight (D-007).
 *
 * @property int $id
 * @property int $shipment_id
 * @property string $barcode
 * @property string|null $source_barcode
 * @property string|null $description
 * @property numeric $weight_kg
 * @property int|null $custom_rate_per_kg_cents
 * @property int|null $fixed_charge_cents
 * @property PackageStatus $status
 * @property bool $is_delayed
 * @property string|null $delay_reason
 * @property bool $delay_reason_is_public
 * @property Carbon|null $delayed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shipment $shipment
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereSourceBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereWeightKg($value)
 *
 * @mixin \Eloquent
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'weight_kg',
        'custom_rate_per_kg_cents',
        'fixed_charge_cents',
        'status',
        'source_barcode',
        'description',
        'barcode',
        'is_delayed',
        'delay_reason',
        'delay_reason_is_public',
        'delayed_at',
    ];

    protected $attributes = [
        'status' => PackageStatus::Created->value,
        'is_delayed' => false,
        'delay_reason_is_public' => false,
    ];

    protected function casts(): array
    {
        return [
            'status' => PackageStatus::class,
            'weight_kg' => 'decimal:4',
            'custom_rate_per_kg_cents' => 'integer',
            'fixed_charge_cents' => 'integer',
            'is_delayed' => 'boolean',
            'delay_reason_is_public' => 'boolean',
            'delayed_at' => 'datetime',
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
        $sync = fn (self $package) => $package->shipment->recalculateTotalWeight();

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

    /** The public tracking URL a customer reaches by scanning this package. */
    public function trackingUrl(): string
    {
        return route('tracking.show', $this->barcode);
    }
}
