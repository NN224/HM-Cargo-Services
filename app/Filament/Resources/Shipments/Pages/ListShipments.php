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
/* Clean up panel backgrounds */
.fi-ta-panel,
.dark .fi-ta-panel { 
    background-color: transparent !important; 
    box-shadow: none !important; 
    border: none !important; 
    --tw-ring-shadow: 0 0 #0000 !important; 
} 
.fi-ta-content { 
    background-color: transparent !important; 
    border: none !important; 
}

.fi-ta-ctn {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
}

/* Style single unified dark header box panel containing batch cards & search toolbar */
.fi-ta-header-ctn {
    background: #18181b !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 1rem !important;
    padding: 0.875rem 1rem 0.5rem !important;
    margin-bottom: 1.25rem !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 0.5rem !important;
}

/* Inner search and filter toolbar sits cleanly at bottom of unified container */
.fi-ta-header-toolbar {
    background: transparent !important;
    border: none !important;
    border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
    border-radius: 0 !important;
    padding-top: 0.625rem !important;
    padding-bottom: 0.125rem !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-bottom: 0 !important;
    box-shadow: none !important;
}

/* Completely hide duplicate sorting toolbar box */
.fi-ta-sorting-settings {
    display: none !important;
}

/* Order card actions nicely at top and contain overflow */
.fi-ta-record {
    display: flex;
    flex-direction: column;
    overflow: hidden !important;
    box-sizing: border-box !important;
}
.fi-ta-record > div,
.fi-ta-col-wrp {
    max-width: 100% !important;
    box-sizing: border-box !important;
}
.fi-ta-record > div:last-child:has(.fi-ac-action) {
    order: -1;
    margin-bottom: 0.5rem;
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
