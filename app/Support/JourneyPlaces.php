<?php

namespace App\Support;

use App\Enums\PackageStatus;

/**
 * The named places on a route, and the one wording used for each journey step.
 *
 * These labels existed in three places — the admin shipment card, the public
 * tracking page and the tracking API — and the three had drifted: the admin
 * read "وصلت مستودع دبي" while the customer read "وصل مستودع دبي" for the same
 * scan. The admin wording is the original, so it is the one kept here.
 *
 * Deliberately free of Eloquent: callers pass plain names, so a projection
 * built from a query builder row can use this without loading models.
 */
final readonly class JourneyPlaces
{
    public function __construct(
        private ?string $originWarehouse = null,
        private ?string $originAirport = null,
        private ?string $destinationAirport = null,
        private ?string $deliveryOffice = null,
    ) {}

    public function labelFor(PackageStatus $status): string
    {
        return match ($status) {
            // Warehouses are named for their city ("دبي"), so this step needs
            // the word to read as a place. Airports and offices carry it.
            PackageStatus::ReceivedOrigin => 'وصلت مستودع '.$this->name($this->originWarehouse),
            PackageStatus::ArrivedOriginAirport => 'وصلت '.$this->name($this->originAirport),
            PackageStatus::ArrivedTransit => 'وصلت '.$this->name($this->destinationAirport),
            PackageStatus::ArrivedDestination => 'وصلت '.$this->name($this->deliveryOffice),
            PackageStatus::Collected => 'استلمها العميل',
            // Retired statuses (D-031) still appear on rows written before the
            // change, so they keep resolving to the step they stood for.
            PackageStatus::InTransit => 'وصلت '.$this->name($this->originAirport),
            PackageStatus::DepartedTransit => 'وصلت '.$this->name($this->destinationAirport),
            default => 'غير مضبوط',
        };
    }

    /** A route with an unconfigured place must still read as a sentence. */
    private function name(?string $value): string
    {
        return filled($value) ? $value : 'غير مضبوط';
    }
}
