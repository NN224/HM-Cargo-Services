<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\Capability;
use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Resources\Pages\EditRecord;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    private ?int $finalChargeCents = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['final_charge_usd'] = $this->record->final_charge_cents === null
            ? null
            : number_format($this->record->final_charge_cents / 100, 2, '.', '');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()?->hasCapability(Capability::PriceShipments)) {
            $this->finalChargeCents = filled($data['final_charge_usd'] ?? null)
                ? (int) round(((float) $data['final_charge_usd']) * 100)
                : null;
        }

        unset($data['final_charge_usd']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->finalChargeCents !== null) {
            $this->record->setFinalCharge($this->finalChargeCents);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
