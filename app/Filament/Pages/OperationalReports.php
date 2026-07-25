<?php

namespace App\Filament\Pages;

use App\Enums\Capability;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use BackedEnum;
use UnitEnum;

class OperationalReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'التقارير التشغيلية';

    protected static ?string $title = 'التقارير التشغيلية والمالية';

    protected static ?string $slug = 'operational-reports';

    protected static string|UnitEnum|null $navigationGroup = 'الماليات والتقارير';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.operational-reports';

    public string $activeTab = 'cargo';

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?int $warehouse_id = null;

    public ?int $route_id = null;

    public ?string $payment_method = null;

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_to = now()->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function filterSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تصفية النتائج والمدى الزمني')
                    ->columns(['default' => 1, 'sm' => 2, 'md' => 4])
                    ->components([
                        DatePicker::make('date_from')
                            ->label('من تاريخ')
                            ->default(now()->startOfMonth()->toDateString())
                            ->live(),

                        DatePicker::make('date_to')
                            ->label('إلى تاريخ')
                            ->default(now()->toDateString())
                            ->live(),

                        Select::make('warehouse_id')
                            ->label('المستودع')
                            ->options(Warehouse::pluck('name', 'id'))
                            ->placeholder('جميع المستودعات')
                            ->nullable()
                            ->live(),

                        Select::make('route_id')
                            ->label('خط الشحن')
                            ->options(Route::pluck('name', 'id'))
                            ->placeholder('جميع الخطوط')
                            ->nullable()
                            ->live(),
                    ]),
            ]);
    }

    public function canViewMoney(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdministrator()
            || $user->hasCapability(Capability::RecordPayments);
    }

    /**
     * Report 1: Cargo & Warehouse Activity Report
     */
    public function getCargoReport(): array
    {
        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_to)->endOfDay();

        $shipmentsQuery = Shipment::query()->whereBetween('created_at', [$from, $to]);
        if ($this->warehouse_id) {
            $shipmentsQuery->whereHas('customer', function ($q) {
                // filter by origin/customer scope if needed
            });
        }

        $totalShipments = (clone $shipmentsQuery)->count();

        $packagesQuery = Package::query()->whereBetween('created_at', [$from, $to]);
        $totalPackages = (clone $packagesQuery)->count();
        $totalWeightGrams = (clone $packagesQuery)->sum('weight_grams');
        $totalWeightKg = number_format($totalWeightGrams / 1000, 2);

        $awaitingPackages = Package::query()
            ->whereIn('status', [PackageStatus::ReceivedOrigin->value, PackageStatus::ArrivedTransit->value])
            ->count();

        $deliveredPackages = Package::query()
            ->where('status', PackageStatus::Collected->value)
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        return [
            'total_shipments' => $totalShipments,
            'total_packages' => $totalPackages,
            'total_weight_kg' => $totalWeightKg,
            'awaiting_packages' => $awaitingPackages,
            'delivered_packages' => $deliveredPackages,
        ];
    }

    /**
     * Report 2: Route & Freight Performance Report
     */
    public function getRouteReport(): array
    {
        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_to)->endOfDay();

        $routes = Route::with(['originWarehouse', 'destinationWarehouse'])->get();

        $results = [];

        foreach ($routes as $route) {
            if ($this->route_id && $route->id !== (int) $this->route_id) {
                continue;
            }

            $batches = Batch::where('route_id', $route->id)
                ->whereBetween('created_at', [$from, $to])
                ->get();

            $batchIds = $batches->pluck('id');
            $shipments = Shipment::whereIn('batch_id', $batchIds)->get();

            $totalWeightGrams = $shipments->sum('total_weight_grams');
            $totalRevenueCents = $shipments->sum('final_charge_cents');
            $totalCostCents = $batches->sum(fn (\App\Models\Batch $b) => $b->costCents());
            $netProfitCents = $totalRevenueCents - $totalCostCents;

            $results[] = [
                'route_name' => $route->name,
                'batches_count' => $batches->count(),
                'total_weight_kg' => number_format($totalWeightGrams / 1000, 2),
                'total_revenue_usd' => number_format($totalRevenueCents / 100, 2),
                'total_cost_usd' => number_format($totalCostCents / 100, 2),
                'net_profit_usd' => number_format($netProfitCents / 100, 2),
                'is_profitable' => $netProfitCents >= 0,
            ];
        }

        return $results;
    }

    /**
     * Report 3: Financial & Collections Report
     */
    public function getFinancialReport(): array
    {
        if (! $this->canViewMoney()) {
            return [];
        }

        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_to)->endOfDay();

        $query = Payment::query()
            ->where('type', '!=', 'reversal')
            ->whereBetween('collected_at', [$from, $to]);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        if ($this->payment_method) {
            $query->where('method', $this->payment_method);
        }

        $totalCollectedCents = (clone $query)->sum('amount_cents');
        $cashCollectedCents = (clone $query)->where('method', Payment::METHOD_CASH)->sum('amount_cents');
        $whishCollectedCents = (clone $query)->where('method', Payment::METHOD_WHISH)->sum('amount_cents');
        $bankCollectedCents = (clone $query)->where('method', Payment::METHOD_BANK)->sum('amount_cents');
        $otherCollectedCents = (clone $query)->where('method', Payment::METHOD_OTHER)->sum('amount_cents');

        $recentPayments = (clone $query)
            ->with(['customer', 'warehouse', 'collector'])
            ->latest('collected_at')
            ->limit(20)
            ->get();

        return [
            'total_collected_usd' => number_format($totalCollectedCents / 100, 2),
            'cash_usd' => number_format($cashCollectedCents / 100, 2),
            'whish_usd' => number_format($whishCollectedCents / 100, 2),
            'bank_usd' => number_format($bankCollectedCents / 100, 2),
            'other_usd' => number_format($otherCollectedCents / 100, 2),
            'recent_payments' => $recentPayments,
        ];
    }

    /**
     * Report 4: Exceptions & Missing Packages Report
     */
    public function getExceptionsReport(): array
    {
        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_to)->endOfDay();

        $missingPackages = Package::with(['shipment.customer'])
            ->where('status', PackageStatus::Missing->value)
            ->whereBetween('updated_at', [$from, $to])
            ->get();

        $damagedPackages = Package::with(['shipment.customer'])
            ->where('status', PackageStatus::Damaged->value)
            ->whereBetween('updated_at', [$from, $to])
            ->get();

        $cancelledShipments = Shipment::with(['customer'])
            ->where('status', ShipmentStatus::Cancelled->value)
            ->whereBetween('updated_at', [$from, $to])
            ->get();

        return [
            'missing_count' => $missingPackages->count(),
            'damaged_count' => $damagedPackages->count(),
            'cancelled_count' => $cancelledShipments->count(),
            'missing_packages' => $missingPackages,
            'damaged_packages' => $damagedPackages,
            'cancelled_shipments' => $cancelledShipments,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'warehouses' => Warehouse::all(),
            'routes' => Route::all(),
            'cargo' => $this->activeTab === 'cargo' ? $this->getCargoReport() : null,
            'routeData' => $this->activeTab === 'routes' ? $this->getRouteReport() : null,
            'fin' => $this->activeTab === 'financial' && $this->canViewMoney() ? $this->getFinancialReport() : null,
            'exc' => $this->activeTab === 'exceptions' ? $this->getExceptionsReport() : null,
        ];
    }
}
