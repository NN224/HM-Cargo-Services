<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ListShipments extends ListRecords
{
    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString('<style>
/* Remove the big panel background, borders and shadow from container in Filament v5 */
.fi-ta-ctn, .fi-ta-content-ctn, .fi-ta-header-toolbar, .fi-ta-header-ctn { 
    background-color: transparent !important; 
    background: transparent !important;
    box-shadow: none !important; 
    border: none !important; 
    --tw-ring-shadow: 0 0 #0000 !important; 
    --tw-ring-color: transparent !important;
} 
.dark .fi-ta-ctn, .dark .fi-ta-content-ctn, .dark .fi-ta-header-toolbar, .dark .fi-ta-header-ctn { 
    background-color: transparent !important; 
    background: transparent !important;
    --tw-ring-shadow: 0 0 #0000 !important; 
    border: none !important; 
    box-shadow: none !important; 
} 

/* Move actions to the top of the card */
.fi-ta-record-content-ctn {
    display: flex;
    flex-direction: column;
}
.fi-ta-record-content-ctn > div:last-child {
    order: -1;
    margin-bottom: 0.75rem;
    display: flex;
    justify-content: flex-end;
}
</style>');
    }

    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
