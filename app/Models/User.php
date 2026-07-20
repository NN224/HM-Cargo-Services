<?php

namespace App\Models;

use App\Enums\Capability;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'warehouse_id', 'is_active', 'capabilities'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Database defaults are not reflected back onto a freshly created model
     * instance, which would leave `is_active` null until reloaded. Declaring
     * them here keeps a new user consistent in memory and in storage.
     */
    protected $attributes = [
        'role' => UserRole::WarehouseEmployee->value,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'capabilities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Gate access to the admin panel. Deactivated accounts are refused
     * entry rather than deleted, per the no-hard-delete rule.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    /**
     * An employee may only touch records belonging to their own warehouse.
     * Administrators are unrestricted (D-003).
     */
    public function canAccessWarehouse(?int $warehouseId): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        // An employee with no assignment is scoped to nothing, not everything.
        return $warehouseId !== null && $this->warehouse_id === $warehouseId;
    }

    /**
     * Whether this user may perform a specific privileged action (D-022).
     *
     * Capabilities grant an action, never another warehouse's data — warehouse
     * scoping is a separate check that a capability can never widen.
     */
    public function hasCapability(Capability $capability): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        return in_array($capability->value, $this->capabilityList(), true);
    }

    /**
     * The capabilities actually granted, ignoring anything unrecognised.
     *
     * A value that does not match the fixed enum is discarded rather than
     * trusted, so a hand-edited row cannot grant something that does not
     * exist.
     *
     * @return array<int, string>
     */
    public function capabilityList(): array
    {
        $valid = array_column(Capability::cases(), 'value');

        return array_values(array_intersect((array) ($this->capabilities ?? []), $valid));
    }

    public function grantCapability(Capability $capability): void
    {
        $this->forceFill([
            'capabilities' => array_values(array_unique(
                [...$this->capabilityList(), $capability->value]
            )),
        ])->save();
    }

    public function revokeCapability(Capability $capability): void
    {
        $this->forceFill([
            'capabilities' => array_values(
                array_diff($this->capabilityList(), [$capability->value])
            ),
        ])->save();
    }
}
