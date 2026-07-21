<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use App\Services\BatchReportService;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class BatchReport extends ViewRecord
{
    protected static string $resource = BatchResource::class;

    protected static ?string $title = 'تقرير الرحلة';

    public function infolist(Schema $schema): Schema
    {
        $batch = $this->record;
        $report = (new BatchReportService)->getReport($batch);

        return $schema
            ->state([
                'reference' => $batch->reference,
                'route' => $batch->route->name,
                'shipment_count' => $report['shipment_count'],
                'package_count' => $report['package_count'],
                'total_weight' => $report['total_weight_kg'].' كغ',
                'revenue' => $this->formatUsd($report['revenue_cents']),
                'cost' => $report['cost_cents'] !== null ? $this->formatUsd((int) $report['cost_cents']) : 'غير متاحة',
                'collected' => $this->formatUsd($report['collected_cents']),
                'outstanding' => $this->formatUsd($report['outstanding_cents']),
                'profit' => $report['profit_cents'] !== null ? $this->formatUsd((int) $report['profit_cents']) : 'غير متاحة',
            ])
            ->components([
                Section::make('معلومات الرحلة')
                    ->schema([
                        TextEntry::make('reference')->label('رقم الرحلة'),
                        TextEntry::make('route')->label('المسار'),
                    ])
                    ->columns(2),

                Section::make('إحصائيات تشغيلية')
                    ->schema([
                        TextEntry::make('shipment_count')->label('عدد الشحنات'),
                        TextEntry::make('package_count')->label('عدد الطرود'),
                        TextEntry::make('total_weight')->label('الوزن الإجمالي'),
                    ])
                    ->columns(3),

                Section::make('ملخص مالي')
                    ->schema([
                        TextEntry::make('revenue')->label('الإيراد'),
                        TextEntry::make('cost')->label('تكلفة الرحلة'),
                        TextEntry::make('collected')->label('المحصَّل'),
                        TextEntry::make('outstanding')->label('المستحق'),
                        TextEntry::make('profit')->label('الربح')
                            ->color($report['profit_cents'] !== null && (int) $report['profit_cents'] >= 0 ? 'success' : 'danger'),
                    ])
                    ->columns(5),
            ]);
    }

    private function formatUsd(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.'$'.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
