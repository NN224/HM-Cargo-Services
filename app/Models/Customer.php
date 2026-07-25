<?php

namespace App\Models;

use App\Models\Concerns\GuardsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * The billing customer: owns route rates, credit, balance, and statements.
 *
 * A customer is distinct from a shipment recipient (D-006). The recipient
 * collects and normally pays, but carries no financial ownership.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property bool $is_credit_customer
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerRate> $rates
 * @property-read int|null $rates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shipment> $shipments
 * @property-read int|null $shipments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsCreditCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @mixin \Eloquent
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

    public function paidCents(): int
    {
        return (int) DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->where('payments.customer_id', $this->id)
            ->where('payments.type', '!=', 'reversal')
            ->sum('payment_allocations.amount_cents');
    }

    public function outstandingCents(): int
    {
        $charged = (int) $this->shipments()->whereNotNull('final_charge_cents')->sum('final_charge_cents');
        $allocated = $this->paidCents();

        return max(0, $charged - $allocated);
    }
}
