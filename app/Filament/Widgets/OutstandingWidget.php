<?php

namespace App\Filament\Widgets;

use App\Enums\Capability;
use App\Models\PaymentAllocation;
use App\Models\Shipment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * What there is to collect, company-wide.
 *
 * Outstanding is total charged minus total allocated — every allocation
 * belongs to some customer's payment, so summing across all customers is the
 * same figure the per-customer statement reconciles to. Money, so gated on the
 * payments capability; an administrator holds it implicitly.
 */
class OutstandingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCapability(Capability::RecordPayments);
    }

    protected function getStats(): array
    {
        $charged = (int) Shipment::query()->sum('final_charge_cents');
        $allocated = (int) PaymentAllocation::query()->sum('amount_cents');

        $outstanding = $charged - $allocated;

        return [
            Stat::make('المستحق', $this->formatUsd($outstanding))
                ->description('إجمالي المبالغ المتبقية على العملاء'),
        ];
    }

    private function formatUsd(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf('%s$%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }
}
