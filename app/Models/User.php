<?php

namespace App\Models;

use App\Enums\Capability;
use App\Enums\LockablePage;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property UserRole $role
 * @property int|null $warehouse_id
 * @property bool $is_active
 * @property array<array-key, mixed>|null $capabilities
 * @property array<array-key, mixed>|null $locked_pages
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Warehouse|null $warehouse
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCapabilities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLockedPages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWarehouseId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'email', 'password', 'role', 'warehouse_id', 'is_active', 'capabilities', 'locked_pages'])]
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
            'locked_pages' => 'array',
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
     * Whether this user may work on cargo travelling a given route.
     *
     * A route touches up to three warehouses, and standing at any one of them
     * is reason enough: Dubai loads it, Beirut handles it in transit, Damascus
     * receives it. An employee assigned to none of them has no business with
     * that cargo.
     *
     * This lives on the user rather than on a Filament resource because both
     * the panel and the intake service ask it, and a domain service must not
     * have to reach into the presentation layer for a rule about permissions.
     */
    public function canUseRoute(Route $route): bool
    {
        return $this->canAccessWarehouse($route->origin_warehouse_id)
            || $this->canAccessWarehouse($route->transit_warehouse_id)
            || $this->canAccessWarehouse($route->destination_warehouse_id);
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

    /**
     * The pages currently locked for this employee, unrecognised keys dropped.
     *
     * Mirrors capabilityList(): a hand-edited row cannot lock a page that does
     * not exist in the registry.
     *
     * @return array<int, string>
     */
    public function lockedPageList(): array
    {
        $valid = array_column(LockablePage::cases(), 'value');

        return array_values(array_intersect((array) ($this->locked_pages ?? []), $valid));
    }

    /**
     * Whether this page is locked for this user.
     *
     * An administrator is never locked — the whole feature is about narrowing
     * an employee, and an administrator holds everything (D-026).
     */
    public function isPageLocked(LockablePage $page): bool
    {
        if ($this->isAdministrator()) {
            return false;
        }

        return in_array($page->value, $this->lockedPageList(), true);
    }

    public function lockPage(LockablePage $page): void
    {
        $this->forceFill([
            'locked_pages' => array_values(array_unique(
                [...$this->lockedPageList(), $page->value]
            )),
        ])->save();
    }

    public function unlockPage(LockablePage $page): void
    {
        $this->forceFill([
            'locked_pages' => array_values(
                array_diff($this->lockedPageList(), [$page->value])
            ),
        ])->save();
    }
}
