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

/* Style top header toolbar */
.fi-ta-ctn {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
}
.fi-ta-header-ctn,
.fi-ta-header-toolbar {
    background: #18181b !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 0.875rem !important;
    padding: 0.5rem 0.875rem !important;
    margin-bottom: 1.25rem !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

/* Style table group headers cleanly */
.fi-ta-group-header-row,
.fi-ta-group-header-cell,
.fi-ta-group-header {
    background-color: #18181b !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 0.75rem !important;
    margin-top: 1.25rem !important;
    margin-bottom: 0.875rem !important;
    padding: 0.625rem 1rem !important;
    font-weight: 700 !important;
    color: #60a5fa !important;
}

/* Order card actions nicely at top */
.fi-ta-record {
    display: flex;
    flex-direction: column;
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
