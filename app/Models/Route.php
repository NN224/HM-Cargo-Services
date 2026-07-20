<?php

namespace App\Models;

use App\Models\Concerns\GuardsDeletion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * A configurable transport route (D-005).
 *
 * Direct and transit routes are separate records and may carry different
 * customer prices, so Dubai->Syria direct and Dubai->Beirut->Syria are never
 * the same route (D-007).
 */
class Route extends Model
{
    use GuardsDeletion, HasFactory;

    protected $fillable = [
        'name',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'transit_warehouse_id',
        'is_active',
    ];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Enforce route sanity in the model.
     *
     * This belongs in the database as a CHECK constraint, but Laravel has no
     * portable check() helper and SQLite cannot add one after table creation.
     * Emitting SQLite-only SQL would break the PostgreSQL parity D-002
     * requires, so the invariant lives here until Phase 7 adds it to
     * PostgreSQL directly. See the note in the create_routes_table migration.
     */
    protected static function booted(): void
    {
        static::saving(function (self $route): void {
            if ($route->origin_warehouse_id === $route->destination_warehouse_id) {
                throw new InvalidArgumentException(
                    'مستودع المنشأ ومستودع الوجهة يجب أن يكونا مختلفين.'
                );
            }

            if ($route->transit_warehouse_id !== null
                && in_array($route->transit_warehouse_id, [
                    $route->origin_warehouse_id,
                    $route->destination_warehouse_id,
                ], true)) {
                throw new InvalidArgumentException(
                    'مستودع العبور يجب أن يختلف عن مستودعي المنشأ والوجهة.'
                );
            }
        });
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function transitWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'transit_warehouse_id');
    }

    /** @return HasMany<CustomerRate, $this> */
    public function customerRates(): HasMany
    {
        return $this->hasMany(CustomerRate::class);
    }

    public function hasTransit(): bool
    {
        return $this->transit_warehouse_id !== null;
    }

    /**
     * Batches carry historical cost against a route, and customer rates are
     * agreed per route.
     *
     * @return array<string, string>
     */
    protected function deletionDependencies(): array
    {
        return ['رحلات' => 'batches', 'أسعار عملاء' => 'customerRates'];
    }

    /** @return HasMany<Batch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
