<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

/**
 * Warehouse authorization.
 *
 * AGENTS.md requires authorization in backend policies, not by hiding UI
 * elements, so every check here must hold even if a Filament button is
 * reachable.
 *
 * Employees may view their own warehouse and nothing else. Only
 * administrators may create, edit, or deactivate warehouses.
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        // Employees may reach the list; the query scope narrows it to their own.
        return true;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->canAccessWarehouse($warehouse->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Deactivation is the only removal path. Hard deletion is forbidden for
     * operational records, so this is denied for everyone.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        return false;
    }
}
