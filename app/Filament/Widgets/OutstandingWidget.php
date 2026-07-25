<?php

namespace App\Filament\Widgets;

use App\Enums\Capability;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Shipment;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Financial overview for users with the RecordPayments capability.
 *
 * Three stats: outstanding balance (all-time), collected this month,
 * and payment operation count this month. Together they fill the 3-column
 * Stats row so no empty space is shown.
 */
class OutstandingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCapability(Capability::RecordPayments);
    }

    protected function getStats(): array
    {
        // Outstanding = total charged minus total allocated (all time).
        $charged = (int) Shipment::query()->sum('final_charge_cents');
        $allocated = (int) PaymentAllocation::query()->sum('amount_cents');
        $outstanding = $charged - $allocated;

        // Current month collections (non-reversal payments).
        $monthStart = Carbon::now()->startOfMonth();
        $collectedThisMonth = (int) Payment::query()
            ->where('type', '!=', 'reversal')
            ->where('collected_at', '>=', $monthStart)
            ->sum('amount_cents');

        // Count of payment operations this month.
        $paymentsThisMonth = Payment::query()
            ->where('type', '!=', 'reversal')
            ->where('collected_at', '>=', $monthStart)
            ->count();

        return [
            Stat::make('المستحق على العملاء', $this->formatUsd($outstanding))
                ->description('إجمالي المبالغ المتبقية لدى العملاء')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color($outstanding > 0 ? 'danger' : 'success'),

            Stat::make('المحصّل هذا الشهر', $this->formatUsd($collectedThisMonth))
                ->description('مجموع المقبوضات منذ بداية الشهر')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('success'),

            Stat::make('عمليات دفع هذا الشهر', (string) $paymentsThisMonth)
                ->description('عدد عمليات الدفع المسجّلة هذا الشهر')
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->color($paymentsThisMonth > 0 ? 'primary' : 'gray'),
        ];
    }

    private function formatUsd(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf('%s$%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }
}
