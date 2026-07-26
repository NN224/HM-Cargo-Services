<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\PackageStatus;
use App\Filament\Resources\Batches\Actions\ManageBatchJourneyAction;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Resources\Shipments\Tables\ShipmentsTable;
use App\Models\Batch;
use App\Models\Package;
use App\Models\Shipment;
use App\Services\PackageJourneyService;
use DomainException;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListShipments extends ListRecords
{
    public ?int $managingBatchId = null;

    public ?int $selectedBatchId = null;

    public string $shipmentSearch = '';

    public ?string $shipmentStatusFilter = null;

    public ?string $bulkTargetStatus = null;

    public string $bulkCorrectionReason = '';

    /** @var array<int, int> */
    public array $selectedShipmentIds = [];

    protected static string $resource = ShipmentResource::class;

    protected string $view = 'filament.resources.shipments.pages.list-shipments';

    public function selectBatch(?int $batchId): void
    {
        $this->selectedBatchId = $batchId;
        $this->selectedShipmentIds = [];
        $this->bulkTargetStatus = null;
        $this->bulkCorrectionReason = '';
        $this->resetTable();
    }

    /** @param array<int, int> $shipmentIds */
    public function selectVisibleShipments(array $shipmentIds): void
    {
        $this->selectedShipmentIds = array_values(array_unique(array_map('intval', $shipmentIds)));
    }

    public function clearShipmentSelection(): void
    {
        $this->selectedShipmentIds = [];
        $this->bulkTargetStatus = null;
        $this->bulkCorrectionReason = '';
    }

    /** @param array<int, int> $shipmentIds */
    public function toggleVisibleShipments(array $shipmentIds): void
    {
        $shipmentIds = array_values(array_unique(array_map('intval', $shipmentIds)));

        if ($shipmentIds !== [] && empty(array_diff($shipmentIds, $this->selectedShipmentIds))) {
            $this->selectedShipmentIds = array_values(array_diff($this->selectedShipmentIds, $shipmentIds));

            return;
        }

        $this->selectedShipmentIds = array_values(array_unique(array_merge($this->selectedShipmentIds, $shipmentIds)));
    }

    public function toggleShipmentSelection(int $shipmentId): void
    {
        if (in_array($shipmentId, $this->selectedShipmentIds, true)) {
            $this->selectedShipmentIds = array_values(array_diff($this->selectedShipmentIds, [$shipmentId]));

            return;
        }

        $this->selectedShipmentIds[] = $shipmentId;
    }

    public function startManageBatchJourney(int $batchId): void
    {
        $batch = Batch::findOrFail($batchId);
        $this->managingBatchId = $batch->id;
        $this->mountAction('manageBatchJourney');
    }

    public function bulkAdvanceSelectedShipments(PackageJourneyService $service): void
    {
        $actor = auth()->user();

        if (! $actor) {
            Notification::make()
                ->title('تعذر تحديث الشحنات')
                ->body('يجب تسجيل الدخول أولاً.')
                ->danger()
                ->send();

            return;
        }

        $shipmentIds = array_values(array_unique(array_map('intval', $this->selectedShipmentIds)));

        if ($shipmentIds === []) {
            Notification::make()
                ->title('اختر شحنة واحدة على الأقل')
                ->warning()
                ->send();

            return;
        }

        $shipments = Shipment::query()
            ->with('packages')
            ->whereKey($shipmentIds)
            ->orderBy('id')
            ->get();

        $targetStatus = null;
        $correctionReason = 'تصحيح جماعي من شاشة الشحنات';
        if (filled($this->bulkTargetStatus)) {
            $targetStatus = PackageStatus::tryFrom((string) $this->bulkTargetStatus);

            if (! $targetStatus || $targetStatus->journeyPosition() === null) {
                Notification::make()
                    ->title('اختر حالة مسار صحيحة')
                    ->danger()
                    ->send();

                return;
            }

            if (! $actor->isAdministrator()) {
                Notification::make()
                    ->title('تصحيح الحالة يتطلب صلاحية المدير')
                    ->danger()
                    ->send();

                return;
            }

            if (trim($this->bulkCorrectionReason) !== '') {
                $correctionReason = trim($this->bulkCorrectionReason);
            }
        }

        $updatedCount = 0;
        $failedCount = 0;

        foreach ($shipments as $shipment) {
            $packageIds = $shipment->packages
                ->filter(fn (Package $package): bool => $package->status->journeyPosition() !== null
                    && $package->status !== PackageStatus::Collected
                    && $package->status !== PackageStatus::Cancelled
                    && ! $package->status->isException())
                ->pluck('id')
                ->all();

            if ($packageIds === []) {
                $failedCount++;

                continue;
            }

            try {
                if ($targetStatus instanceof PackageStatus) {
                    $service->correct($shipment, $packageIds, $targetStatus, $actor, $correctionReason, false);
                } else {
                    $service->advance($shipment, $packageIds, $actor, 'selected_shipments_bulk_progress');
                }

                $updatedCount++;
            } catch (DomainException) {
                $failedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->selectedShipmentIds = [];
            $this->bulkTargetStatus = null;
            $this->bulkCorrectionReason = '';
        }

        Notification::make()
            ->title($updatedCount > 0 ? 'تم تحديث الشحنات المحددة' : 'تعذر تحديث الشحنات المحددة')
            ->body($failedCount > 0 ? "تم تحديث {$updatedCount} وتعذر تحديث {$failedCount}." : "تم تحديث {$updatedCount} شحنات.")
            ->{$updatedCount > 0 ? 'success' : 'danger'}()
            ->send();
    }

    public function table(Table $table): Table
    {
        $table = ShipmentsTable::configure($table);

        if ($this->selectedBatchId !== null) {
            $table->modifyQueryUsing(fn (Builder $query) => $query->where('batch_id', $this->selectedBatchId));
        }

        return $table;
    }

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

/* Style single unified dark header box panel containing search toolbar & active filters */
.fi-ta-header-ctn {
    background: #18181b !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 1rem !important;
    padding: 0.875rem 1rem 0.625rem !important;
    margin-bottom: 1.25rem !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 0.5rem !important;
}

/* Inner search and filter toolbar sits cleanly inside unified container */
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

/* Include Active Filters bar inside the unified dark container box */
.fi-ta-active-filters-ctn,
.fi-ta-filter-indicators,
.fi-ta-header-ctn > div:last-child {
    background: transparent !important;
    border: none !important;
    border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
    border-radius: 0 !important;
    padding-top: 0.625rem !important;
    padding-bottom: 0.25rem !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-top: 0.25rem !important;
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

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ManageBatchJourneyAction::make()
                ->visible(fn (): bool => $this->managingBatchId !== null),
        ];
    }
}
