<?php

namespace App\Services;

use App\Models\Shipment;

/**
 * Builds one-click wa.me links with prefilled Arabic messages.
 *
 * Product-spec 4.7: no paid WhatsApp API. The employee opens the link and
 * sends the message manually; the system records that the link was prepared,
 * not that the message was delivered.
 *
 * Two moments, two messages:
 *   - intake:  when the cargo is received, the sender (customer) gets the
 *              tracking link — the one moment the link is actually useful.
 *   - arrival: when it arrives, the recipient gets the amount and the
 *              invitation to collect. No tracking link — tracking is moot
 *              once the shipment has arrived.
 */
class WhatsAppMessageService
{
    /**
     * The intake link — to the customer (sender), carrying the tracking link.
     */
    public function intakeUrl(Shipment $shipment): string
    {
        return $this->urlFor($shipment->customer->phone, $this->intakeMessage($shipment));
    }

    public function intakeMessage(Shipment $shipment): string
    {
        $name = $shipment->customer->name;
        $reference = $shipment->reference;
        $tracking = url('/track/'.$shipment->public_token);

        return implode("\n", [
            "مرحباً {$name}،",
            "استلمنا شحنتك رقم {$reference}.",
            "تابع حالتها من هنا: {$tracking}",
        ]);
    }

    /**
     * The arrival link — to the recipient, carrying the amount due.
     */
    public function arrivalUrl(Shipment $shipment): string
    {
        return $this->urlFor($shipment->recipient_phone, $this->arrivalMessage($shipment));
    }

    public function arrivalMessage(Shipment $shipment): string
    {
        $name = $shipment->recipient_name;
        $reference = $shipment->reference;
        $warehouse = $shipment->batch?->route?->destinationWarehouse?->name ?? 'مستودع الوصول';
        $total = $this->formatUsd((int) $shipment->final_charge_cents);
        $remaining = $this->formatUsd($shipment->outstandingCents());

        return implode("\n", [
            "مرحباً {$name}،",
            "وصلت شحنتك رقم {$reference} إلى {$warehouse}.",
            "المبلغ الإجمالي: \${$total}",
            "المبلغ المتبقي: \${$remaining}",
            'شحنتك جاهزة للاستلام.',
        ]);
    }

    /**
     * The complete wa.me URL, ready for a single click.
     */
    private function urlFor(string $phone, string $text): string
    {
        return 'https://wa.me/'.$this->normalisePhone($phone).'?text='.urlencode($text);
    }

    /**
     * Strip everything except digits from the phone number.
     *
     * wa.me expects the country code without a leading plus, e.g.
     * 96171112233 rather than +961 71-112233.
     */
    public function normalisePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    /**
     * Integer cents to a two-decimal USD string, e.g. 9250 → "92.50".
     */
    private function formatUsd(int $cents): string
    {
        $absolute = abs($cents);
        $sign = $cents < 0 ? '-' : '';

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
