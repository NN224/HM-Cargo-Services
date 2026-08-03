<?php

namespace App\Services;

use App\Models\Shipment;
use App\Support\Format;

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
            'أو حمّل تطبيقنا للتتبع السريع: https://hmcargoservices.com/track',
        ]);
    }

    /**
     * The arrival link — to the recipient, carrying the amount due.
     */
    public function arrivalUrl(Shipment $shipment): string
    {
        return $this->urlFor($shipment->recipient_phone, $this->arrivalMessage($shipment));
    }

    /**
     * The recipient decides what to bring and how to collect from this one
     * message, so it carries the cargo facts beside the money: how many
     * packages and how heavy, both counting only the packages still on the
     * shipment. It closes with both office numbers, so the recipient can ask
     * about delivery at either end of the route. They come from config: the
     * numbers change, and a number change must not be a deploy.
     */
    public function arrivalMessage(Shipment $shipment): string
    {
        $name = $shipment->recipient_name;
        $reference = $shipment->reference;
        $warehouse = $shipment->batch?->route?->destinationWarehouse->name ?? 'مستودع الوصول';
        $packageCount = $shipment->activePackageCount();
        $weight = Format::weight((string) $shipment->total_weight_kg);
        $total = $this->formatUsd((int) $shipment->final_charge_cents);
        $remaining = $this->formatUsd($shipment->outstandingCents());

        return implode("\n", [
            "مرحباً {$name}،",
            "وصلت شحنتك رقم {$reference} إلى {$warehouse}.",
            "عدد الطرود: {$packageCount}",
            "الوزن: {$weight}",
            "المبلغ الإجمالي: \${$total}",
            "المبلغ المتبقي: \${$remaining}",
            'شحنتك جاهزة للاستلام.',
            '',
            'للتوصيل ومعلومات أخرى التواصل معنا على الواتساب',
            'دبي: '.config('company.whatsapp_dubai'),
            'بيروت: '.config('company.whatsapp_beirut'),
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
     * Strip non-digits and normalize phone numbers for wa.me links.
     *
     * wa.me expects country code without leading plus or 00 (e.g. 96171112233).
     * If a local 8-digit Lebanese number is passed without country code,
     * this automatically prepends the 961 country code so WhatsApp works seamlessly.
     */
    public function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (empty($digits)) {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 8 && ! str_starts_with($digits, '961') && ! str_starts_with($digits, '963') && ! str_starts_with($digits, '971') && ! str_starts_with($digits, '966')) {
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $digits = '961'.$digits;
        } elseif (strlen($digits) === 7 && str_starts_with($digits, '3')) {
            $digits = '961'.$digits;
        }

        return $digits;
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
