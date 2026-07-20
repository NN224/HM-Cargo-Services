<?php

namespace App\Models;

use App\Models\Concerns\GuardsDeletion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The billing customer: owns route rates, credit, balance, and statements.
 *
 * A customer is distinct from a shipment recipient (D-006). The recipient
 * collects and normally pays, but carries no financial ownership.
 */
class Customer extends Model
{
    use GuardsDeletion, HasFactory;

    protected $fillable = ['name', 'phone', 'is_credit_customer', 'is_active'];

    protected $attributes = [
        'is_credit_customer' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_credit_customer' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<CustomerRate, $this> */
    public function rates(): HasMany
    {
        return $this->hasMany(CustomerRate::class);
    }

    /**
     * The customer's agreed price for one route, or null when none is set.
     *
     * Batch assignment must refuse to price a shipment when this is null
     * rather than falling back to a default rate.
     */
    public function rateForRoute(Route $route): ?CustomerRate
    {
        return $this->rates()->where('route_id', $route->id)->first();
    }

    /**
     * Shipments and rates both reference a customer; deleting one holding
     * either would orphan invoices and statements.
     *
     * @return array<string, string>
     */
    protected function deletionDependencies(): array
    {
        return ['شحنات' => 'shipments', 'أسعار' => 'rates'];
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
