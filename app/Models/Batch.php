<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Models\Concerns\GuardsDeletion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A shipment batch: one route, one total cost per kilogram, many shipments.
 *
 * Batches are a core module, not optional tagging (D-004). A batch is what
 * makes its shipments billable, because the customer's price is specific to
 * the route the batch travels.
 *
 * @property int $id
 * @property string $reference
 * @property int $route_id
 * @property BatchStatus $status
 * @property int|null $cost_per_kg_cents
 * @property Carbon|null $dispatched_on
 * @property Carbon|null $arrived_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Route $route
 * @property-read Collection<int, Shipment> $shipments
 * @property-read int|null $shipments_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereArrivedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereCostPerKgCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereDispatchedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Batch extends Model
{
    use GuardsDeletion, HasFactory;

    protected $fillable = [
        'reference', 'route_id', 'status',
        'cost_per_kg_cents', 'dispatched_on', 'arrived_on',
    ];

    protected $attributes = [
        'status' => BatchStatus::Open->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'cost_per_kg_cents' => 'integer',
            'dispatched_on' => 'date',
            'arrived_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $batch): void {
            if (! filled($batch->reference)) {
                $batch->reference = self::generateReference();
            }
        });
    }

    private static function generateReference(): string
    {
        $year = now()->year;
        $sequence = static::whereYear('created_at', $year)->max('id') ?? 0;

        return sprintf('BCH-%d-%04d', $year, $sequence + 1);
    }

    /** @return BelongsTo<Route, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /** Total the customers are billed for this batch, in cents. */
    public function revenueCents(): int
    {
        return (int) $this->shipments()->sum('final_charge_cents');
    }

    /**
     * What the batch cost the company, in cents, or null when unknown.
     *
     * Keeps its cents: the manual customer rounding never applies here
     * (D-008, D-020), so profit stays exact.
     *
     * Null rather than zero when no rate has been entered. Zero would read as
     * "this batch was free" and silently overstate profit by the entire
     * revenue — the one wrong answer nobody would question.
     */
    public function costCents(): ?int
    {
        if ($this->cost_per_kg_cents === null) {
            return null;
        }

        // bcmul keeps the multiplication in decimal, matching how the customer
        // charge is computed in BatchAssignmentService. The weight is summed
        // in SQL because adding decimals in PHP reintroduces float error.
        $weight = (string) $this->shipments()->sum('total_weight_kg');

        return (int) round((float) bcmul($weight, (string) $this->cost_per_kg_cents, 6));
    }

    /** Revenue minus cost, or null while the cost is still unknown. */
    public function profitCents(): ?int
    {
        $cost = $this->costCents();

        if ($cost === null) {
            return null;
        }

        return $this->revenueCents() - $cost;
    }

    /**
     * A batch holding shipments carries their pricing history.
     *
     * @return array<string, string>
     */
    protected function deletionDependencies(): array
    {
        return ['شحنات' => 'shipments'];
    }
}
