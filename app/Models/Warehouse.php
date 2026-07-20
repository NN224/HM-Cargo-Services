<?php

namespace App\Models;

use App\Models\Concerns\GuardsDeletion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use GuardsDeletion, HasFactory;

    protected $fillable = ['name', 'location', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Narrow a query to what the given user is allowed to see.
     *
     * A policy authorizes a single record; it cannot stop a list query from
     * returning every row. Both are required, so this scope must be applied
     * to any query that feeds a list.
     *
     * @param  Builder<Warehouse>  $query
     * @return Builder<Warehouse>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdministrator()) {
            return $query;
        }

        // An unassigned employee sees nothing rather than everything.
        return $query->where('id', $user->warehouse_id ?? 0);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * A warehouse is referenced by routes as origin or destination, and by
     * the employees assigned to it.
     *
     * @return array<string, string>
     */
    protected function deletionDependencies(): array
    {
        return [
            'مسارات' => 'routesAsOrigin',
            'مسارات وجهة' => 'routesAsDestination',
            'موظفين' => 'users',
        ];
    }

    /** @return HasMany<Route, $this> */
    public function routesAsOrigin(): HasMany
    {
        return $this->hasMany(Route::class, 'origin_warehouse_id');
    }

    /** @return HasMany<Route, $this> */
    public function routesAsDestination(): HasMany
    {
        return $this->hasMany(Route::class, 'destination_warehouse_id');
    }
}
