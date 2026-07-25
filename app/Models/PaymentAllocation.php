<?php

namespace App\Models;

use App\Models\Concerns\GuardsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payment_id
 * @property int $shipment_id
 * @property int $amount_cents
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Payment $payment
 * @property-read \App\Models\Shipment $shipment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereAmountCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAllocation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PaymentAllocation extends Model
{
    use GuardsDeletion;

    protected $fillable = [
        'payment_id',
        'shipment_id',
        'amount_cents',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<Shipment, $this> */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    protected function deletionDependencies(): array
    {
        return [];
    }
}
