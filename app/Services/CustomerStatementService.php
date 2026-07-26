<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;

/**
 * Builds a chronological customer statement (pricing-payments.md section 10).
 *
 * Every line carries a running balance. The final balance must reconcile
 * exactly with total shipment outstanding minus unapplied customer credit.
 */
class CustomerStatementService
{
    /**
     * @return array<int, array{date: string, type: string, description: string, amount_cents: int, running_balance_cents: int}>
     */
    public function getStatement(Customer $customer): array
    {
        $lines = [];

        foreach ($customer->shipments as $shipment) {
            if ($shipment->final_charge_cents === null || $shipment->priced_at === null) {
                continue;
            }

            $lines[] = [
                'date' => $this->normaliseTimestamp($shipment->priced_at),
                'type' => 'charge',
                'description' => "شحنة {$shipment->reference}",
                'amount_cents' => (int) $shipment->final_charge_cents,
                'running_balance_cents' => 0,
            ];
        }

        $payments = Payment::where('customer_id', $customer->id)
            ->orderBy('collected_at')
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $label = $payment->isReversal() ? 'reversal' : 'payment';
            $lines[] = [
                'date' => $this->normaliseTimestamp($payment->collected_at),
                'type' => $label,
                'description' => $this->paymentDescription($payment),
                'amount_cents' => (int) $payment->amount_cents,
                'running_balance_cents' => 0,
            ];
        }

        $allocations = PaymentAllocation::whereHas('payment', fn ($q) => $q->where('customer_id', $customer->id)
        )
            ->with('payment')
            ->get()
            ->sortBy(fn ($a) => $a->payment->collected_at->timestamp)
            ->values();

        foreach ($allocations as $allocation) {
            $lines[] = [
                'date' => $this->normaliseTimestamp($allocation->payment->collected_at),
                'type' => 'allocation',
                'description' => "تخصيص {$allocation->shipment->reference}",
                'amount_cents' => (int) $allocation->amount_cents,
                'running_balance_cents' => 0,
            ];
        }

        usort($lines, fn ($a, $b) => $a['date'] <=> $b['date'] ?: ($a['type'] <=> $b['type'])
        );

        $balance = 0;
        foreach ($lines as &$line) {
            // Charges increase what the customer owes.
            // Payments decrease what the customer owes.
            // Reversals (negative amount) decrease by a negative → increase.
            // Allocations are internal transfers — they do not move the balance.
            $balance += match ($line['type']) {
                'charge' => $line['amount_cents'],
                'payment', 'reversal' => -$line['amount_cents'],
                default => 0,
            };
            $line['running_balance_cents'] = $balance;
        }

        return $lines;
    }

    /**
     * @return array{total_charged_cents: int, total_paid_cents: int, outstanding_cents: int, unapplied_credit_cents: int}
     */
    public function getSummary(Customer $customer): array
    {
        $charged = (int) $customer->shipments()->sum('final_charge_cents');
        $paid = (int) Payment::where('customer_id', $customer->id)->sum('amount_cents');
        $allocated = (int) PaymentAllocation::whereHas('payment', fn ($q) => $q->where('customer_id', $customer->id)
        )->sum('amount_cents');

        return [
            'total_charged_cents' => $charged,
            'total_paid_cents' => $paid,
            'outstanding_cents' => $charged - $allocated,
            'unapplied_credit_cents' => $paid - $allocated,
        ];
    }

    private function paymentDescription(Payment $payment): string
    {
        $receipt = $payment->receipt_number;
        $method = match ($payment->method) {
            Payment::METHOD_CASH => 'نقد',
            Payment::METHOD_WHISH => 'Whish',
            Payment::METHOD_BANK => 'حوالة بنكية',
            default => $payment->custom_method_name ?? 'أخرى',
        };

        if ($payment->isReversal()) {
            return "عكس دفعة {$receipt}";
        }

        return "دفعة {$receipt} ({$method})";
    }

    private function normaliseTimestamp(mixed $value): string
    {
        if ($value === null) {
            return '1970-01-01 00:00:00';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
