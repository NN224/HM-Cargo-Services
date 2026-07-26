<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Shipment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Handles recording payments and allocating them to shipments.
 *
 * All financial mutations must be transactional. Recording a payment
 * and distributing its value to shipments must either fully succeed
 * or fail together to prevent orphaned credit or underpaid shipments.
 */
class PaymentService
{
    /**
     * @param  array  $data  Expected keys: customer_id, amount_cents, method,
     *                       custom_method_name, collected_at, collected_by,
     *                       warehouse_id, reference, notes
     * @param  Shipment|null  $targetShipment  Optional. If provided, payment prioritizes this shipment.
     *
     * @throws DomainException when the payment cannot be processed.
     */
    public function recordPayment(array $data, ?Shipment $targetShipment = null): Payment
    {
        return DB::transaction(function () use ($data, $targetShipment): Payment {
            // Lock the customer to serialize concurrent payment processing for the same account
            $customer = Customer::lockForUpdate()->findOrFail($data['customer_id']);

            if ($data['amount_cents'] <= 0) {
                throw new DomainException('يجب أن يكون مبلغ الدفعة أكبر من صفر.');
            }

            if ($data['method'] === Payment::METHOD_OTHER && empty($data['custom_method_name'])) {
                throw new DomainException("يجب تحديد اسم طريقة الدفع إذا تم اختيار 'أخرى'.");
            }

            // An administrator carries no warehouse of their own, so the
            // warehouse has to be chosen rather than copied from the user.
            // Refusing here turns a raw database constraint error — which is
            // what the operator used to see — into something they can act on.
            if (empty($data['warehouse_id'])) {
                throw new DomainException('يجب تحديد المستودع الذي حُصّلت فيه الدفعة.');
            }

            $payment = new Payment([
                'customer_id' => $customer->id,
                'amount_cents' => (int) $data['amount_cents'],
                'method' => $data['method'],
                'custom_method_name' => $data['custom_method_name'] ?? null,
                'collected_at' => $data['collected_at'],
                'collected_by' => $data['collected_by'],
                'warehouse_id' => $data['warehouse_id'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'receipt_number' => $this->generateReceiptNumber(),
                'type' => Payment::TYPE_PAYMENT,
            ]);

            $payment->save();

            $remainingCents = $payment->amount_cents;

            // Allocate to target shipment first if specified
            if ($targetShipment !== null && $remainingCents > 0) {
                if ($targetShipment->customer_id !== $customer->id) {
                    throw new DomainException('لا يمكن تخصيص الدفعة لشحنة لا تخص هذا العميل.');
                }

                $targetShipment = Shipment::lockForUpdate()->findOrFail($targetShipment->id);
                $remainingCents = $this->allocateToShipment($payment, $targetShipment, $remainingCents);
            }

            // Allocate remaining amount to oldest outstanding shipments (FIFO)
            if ($remainingCents > 0) {
                $outstandingShipments = Shipment::where('customer_id', $customer->id)
                    ->whereColumn('final_charge_cents', '>', 'paid_amount_cents')
                    ->whereNotNull('final_charge_cents')
                    ->orderBy('priced_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($outstandingShipments as $shipment) {
                    if ($remainingCents <= 0) {
                        break;
                    }
                    $remainingCents = $this->allocateToShipment($payment, $shipment, $remainingCents);
                }
            }

            return $payment;
        });
    }

    /**
     * Reverses an existing payment.
     *
     * We never hard-delete financial records (pricing-payments.md D-021).
     * Instead, we create a negative compensating payment and unallocate
     * the original payment's allocations.
     *
     * @throws DomainException when reversal is invalid.
     */
    public function reversePayment(Payment $originalPayment, User $admin, string $reason): Payment
    {
        return DB::transaction(function () use ($originalPayment, $admin, $reason): Payment {
            $originalPayment = Payment::lockForUpdate()->findOrFail($originalPayment->id);

            if ($originalPayment->isReversal()) {
                throw new DomainException('لا يمكن عكس دفعة هي بحد ذاتها عملية عكس.');
            }

            if (Payment::where('reverses_payment_id', $originalPayment->id)->exists()) {
                throw new DomainException('تم عكس هذه الدفعة مسبقاً.');
            }

            if (empty(trim($reason))) {
                throw new DomainException('سبب العكس إلزامي.');
            }

            $reversal = new Payment([
                'customer_id' => $originalPayment->customer_id,
                'amount_cents' => -$originalPayment->amount_cents,
                'method' => $originalPayment->method,
                'custom_method_name' => $originalPayment->custom_method_name,
                'collected_at' => now(),
                'collected_by' => $admin->id,
                'warehouse_id' => $originalPayment->warehouse_id,
                'reference' => $originalPayment->reference,
                'notes' => $originalPayment->notes,
                'receipt_number' => $this->generateReceiptNumber(),
                'type' => Payment::TYPE_REVERSAL,
                'reverses_payment_id' => $originalPayment->id,
                'reversal_reason' => $reason,
            ]);

            $reversal->save();

            $allocations = PaymentAllocation::where('payment_id', $originalPayment->id)
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                $shipment = Shipment::lockForUpdate()->findOrFail($allocation->shipment_id);

                $shipment->decrement('paid_amount_cents', $allocation->amount_cents);

                PaymentAllocation::create([
                    'payment_id' => $reversal->id,
                    'shipment_id' => $shipment->id,
                    'amount_cents' => -$allocation->amount_cents,
                ]);
            }

            return $reversal;
        });
    }

    /**
     * Allocates a portion of available funds to a shipment.
     * Updates the shipment's paid amount.
     *
     * @return int The remaining funds after allocation.
     */
    private function allocateToShipment(Payment $payment, Shipment $shipment, int $availableCents): int
    {
        $outstanding = $shipment->outstandingCents();

        if ($outstanding <= 0) {
            return $availableCents;
        }

        $allocationAmount = min($outstanding, $availableCents);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'shipment_id' => $shipment->id,
            'amount_cents' => $allocationAmount,
        ]);

        $shipment->increment('paid_amount_cents', $allocationAmount);

        return $availableCents - $allocationAmount;
    }

    /**
     * Generates a unique sequential receipt number.
     */
    private function generateReceiptNumber(): string
    {
        $year = now()->year;
        $sequence = Payment::whereYear('created_at', $year)->max('id') ?? 0;

        return sprintf('REC-%d-%06d', $year, $sequence + 1);
    }
}
