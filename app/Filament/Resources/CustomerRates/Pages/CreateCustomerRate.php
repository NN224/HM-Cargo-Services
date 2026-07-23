<?php

namespace App\Filament\Resources\CustomerRates\Pages;

use App\Filament\Resources\CustomerRates\CustomerRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerRate extends CreateRecord
{
    protected static string $resource = CustomerRateResource::class;

    /**
     * Return to the list after saving.
     *
     * Filament's defaults send a new record to its own edit form and leave a
     * saved record where it is, which reads as "nothing happened" to an
     * operator working through a queue of records.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
