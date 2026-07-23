<?php

namespace App\Services;

use App\Models\Shipment;

/**
 * Builds a one-click wa.me link with a prefilled Arabic arrival message.
 *
 * Product-spec 4.7: no paid WhatsApp API. The employee opens the link and
 * sends the message manually; the system records that the link was
 * prepared, not that the message was delivered.
 */
class WhatsAppMessageService
{
    /**
     * The complete wa.me URL, ready for a single click.
     */
    public function buildUrl(Shipment $shipment): string
    {
        $phone = $this->normalisePhone($shipment->recipient_phone);
        $text = $this->buildMessage($shipment);

        return 'https://wa.me/'.$phone.'?text='.urlencode($text);
    }

    /**
     * The plain Arabic text that will be prefilled in WhatsApp.
     */
    public function buildMessage(Shipment $shipment): string
    {
        $name = $shipment->recipient_name;
        $reference = $shipment->reference;
        $warehouse = $shipment->batch?->route?->destinationWarehouse?->name ?? 'مستودع الوصول';
        $total = $this->formatUsd((int) $shipment->final_charge_cents);
        $remaining = $this->formatUsd($shipment->outstandingCents());
        $tracking = url('/track/'.$shipment->public_token);

        return implode("\n", [
            "مرحباً {$name}،",
            "وصلت شحنتك رقم {$reference} إلى {$warehouse}.",
            "المبلغ الإجمالي: \${$total}",
            "المبلغ المتبقي: \${$remaining}",
            "رابط التتبع: {$tracking}",
        ]);
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
