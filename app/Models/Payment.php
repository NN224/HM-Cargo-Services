<?php

namespace App\Models;

use App\Models\Concerns\GuardsDeletion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $amount_cents
 * @property string $method
 * @property string|null $custom_method_name
 * @property Carbon $collected_at
 * @property int $collected_by
 * @property int $warehouse_id
 * @property string|null $reference
 * @property string|null $notes
 * @property string $receipt_number
 * @property string $type
 * @property int|null $reverses_payment_id
 * @property string|null $reversal_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PaymentAllocation> $allocations
 * @property-read int|null $allocations_count
 * @property-read User $collector
 * @property-read Customer $customer
 * @property-read Payment|null $reversedPayment
 * @property-read Warehouse $warehouse
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmountCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCollectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCollectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCustomMethodName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReceiptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReversalReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReversesPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereWarehouseId($value)
 *
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use GuardsDeletion;

    const METHOD_CASH = 'cash';

    const METHOD_WHISH = 'whish';

    const METHOD_BANK = 'bank_transfer';

    const METHOD_OTHER = 'other';

    const TYPE_PAYMENT = 'payment';

    const TYPE_REVERSAL = 'reversal';

    protected $fillable = [
        'customer_id',
        'amount_cents',
        'method',
        'custom_method_name',
        'collected_at',
        'collected_by',
        'warehouse_id',
        'reference',
        'notes',
        'receipt_number',
        'type',
        'reverses_payment_id',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'collected_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function reversedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'reverses_payment_id');
    }

    public function isReversal(): bool
    {
        return $this->type === self::TYPE_REVERSAL;
    }

    protected function deletionDependencies(): array
    {
        return []; // No deletions allowed at all, see GuardsDeletion usage or policy
    }
}
