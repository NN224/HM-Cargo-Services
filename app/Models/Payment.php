<?php

namespace App\Models;

use App\Models\Concerns\GuardsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
