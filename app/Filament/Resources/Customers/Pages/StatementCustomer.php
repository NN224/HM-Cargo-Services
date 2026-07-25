<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\CustomerStatementService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

/**
 * @property \App\Models\Customer $record
 */
class StatementCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'كشف الحساب';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label('طباعة كشف الحساب')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (\App\Models\Customer $record) => route('customers.statement.print', $record))
                ->openUrlInNewTab(),
        ];
    }

    private function statementService(): CustomerStatementService
    {
        return app(CustomerStatementService::class);
    }

    public function infolist(Schema $schema): Schema
    {
        $customer = $this->record;
        $lines = $this->statementService()->getStatement($customer);
        $summary = $this->statementService()->getSummary($customer);

        return $schema
            ->state([
                'lines' => $lines,
                'summary_charged' => $this->formatUsd($summary['total_charged_cents']),
                'summary_paid' => $this->formatUsd($summary['total_paid_cents']),
                'summary_outstanding' => $this->formatUsd($summary['outstanding_cents']),
                'summary_unapplied' => $this->formatUsd($summary['unapplied_credit_cents']),
            ])
            ->components([
                Section::make('معلومات الفاتورة والشركة')
                    ->schema([
                        TextEntry::make('company_name')->label('شركة الشحن')->default('HM Cargo Services'),
                        TextEntry::make('company_phone')->label('هاتف الشركة')->default("🇦🇪 +971 52 153 0190\n🇱🇧 +961 81 059 063")->extraAttributes(['dir' => 'ltr', 'style' => 'text-align: right;']),
                        TextEntry::make('customer_name')->label('اسم العميل')->default($customer->name),
                        TextEntry::make('customer_phone')->label('هاتف العميل')->default($customer->phone)->extraAttributes(['dir' => 'ltr', 'style' => 'text-align: right;']),
                    ])
                    ->columns(4),

                Section::make('ملخص الحساب')
                    ->schema([
                        TextEntry::make('summary_charged')
                            ->label('إجمالي الفوترة'),
                        TextEntry::make('summary_paid')
                            ->label('إجمالي المدفوع'),
                        TextEntry::make('summary_outstanding')
                            ->label('المستحق'),
                        TextEntry::make('summary_unapplied')
                            ->label('رصيد غير مخصّص'),
                    ])
                    ->columns(4),

                Section::make('حركات الحساب')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label('')
                            ->schema([
                                TextEntry::make('date')
                                    ->label('التاريخ')
                                    ->columnSpan(2),
                                TextEntry::make('description')
                                    ->label('البيان')
                                    ->columnSpan(4),
                                TextEntry::make('amount')
                                    ->label('المبلغ')
                                    ->formatStateUsing(fn ($state): string => $this->formatUsd((int) $state))
                                    ->columnSpan(2),
                                TextEntry::make('running')
                                    ->label('الرصيد')
                                    ->formatStateUsing(fn ($state): string => $this->formatUsd((int) $state))
                                    ->columnSpan(2),
                            ])
                            ->columns(10),
                    ]),
            ]);
    }

    private function formatUsd(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.'$'.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
