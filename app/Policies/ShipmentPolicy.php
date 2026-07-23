<?php

namespace App\Policies;

use App\Enums\Capability;
use App\Models\Shipment;
use App\Models\User;

/**
 * Shipment authorization.
 *
 * Viewing is universal by decision: every employee sees every shipment
 * (D-025), which is also what the public-facing label and tracking screens
 * rely on when an authenticated employee opens them. Deletion stays gated on
 * the delete capability and, separately, on Shipment::deleteSafely() refusing
 * a record anything depends on (D-021) — the capability never overrides a
 * dependency.
 *
 * This policy is consulted by Filament's ShipmentResource as well as by the
 * labels route (Gate::authorize('view', $shipment)); every standard method is
 * defined so the resource keeps its current behaviour rather than being
 * denied on a missing method.
 */
class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return true;
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->hasCapability(Capability::DeleteRecords);
    }
}
