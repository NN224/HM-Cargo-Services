<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * A customer's agreed price per kilogram for one route (D-007).
 *
 * Money is stored as integer cents. Binary floating point is never used for
 * money anywhere in this system.
 *
 * Effective-dating is deferred past version 1 (D-018): there is exactly one
 * current rate per customer per route, and history is preserved by the rate
 * snapshot taken on the shipment at batch assignment.
 *
 * @property int $id
 * @property int $customer_id
 * @property int $route_id
 * @property int $rate_per_kg_cents
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\Route $route
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate whereRatePerKgCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerRate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CustomerRate extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'route_id', 'rate_per_kg_cents'];

    protected function casts(): array
    {
        return ['rate_per_kg_cents' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $rate): void {
            if ($rate->rate_per_kg_cents === null || $rate->rate_per_kg_cents <= 0) {
                throw new InvalidArgumentException('السعر لكل كيلوغرام يجب أن يكون أكبر من صفر.');
            }
        });
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Route, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /** Display value in whole dollars, for UI only. Never used in arithmetic. */
    public function ratePerKgDollars(): float
    {
        return $this->rate_per_kg_cents / 100;
    }
}
