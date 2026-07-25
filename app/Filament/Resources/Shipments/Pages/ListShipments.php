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
        $selectorBarHtml = view('filament.tables.components.batch-selector-bar')->render();

        $css = <<<'HTML'
<style>
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

/* Style top container transparently */
.fi-ta-ctn,
.fi-ta-header-ctn {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin-bottom: 0 !important;
}

/* Style single header search/filter toolbar cleanly */
.fi-ta-header-toolbar {
    background: #18181b !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 0.875rem !important;
    padding: 0.625rem 1rem !important;
    margin-bottom: 1rem !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

/* Completely hide duplicate sorting toolbar box */
.fi-ta-sorting-settings,
.fi-ta-header-ctn > div:not(.fi-ta-header-toolbar) {
    display: none !important;
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
</style>
HTML;

        return new HtmlString($selectorBarHtml.$css);
    }

    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
