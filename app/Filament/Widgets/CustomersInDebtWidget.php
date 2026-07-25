<?php

namespace App\Filament\Widgets;

use App\Enums\Capability;
use App\Models\Customer;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The follow-up-for-payment list: customers still owing, largest first.
 *
 * Outstanding is charged minus allocated, computed here as a select expression
 * so the table can order and filter by it — the same formula the statement
 * reconciles to. Money, so gated on the payments capability.
 */
class CustomersInDebtWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'عملاء عليهم رصيد';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCapability(Capability::RecordPayments);
    }

    public function table(Table $table): Table
    {
        $charged = '(SELECT COALESCE(SUM(final_charge_cents), 0) FROM shipments WHERE shipments.customer_id = customers.id)';
        $allocated = '(SELECT COALESCE(SUM(pa.amount_cents), 0) FROM payment_allocations pa '
            .'INNER JOIN payments p ON p.id = pa.payment_id WHERE p.customer_id = customers.id)';

        return $table
            ->query(
                Customer::query()
                    ->select('customers.*')
                    ->selectRaw("($charged - $allocated) as outstanding_cents")
                    // whereRaw, not havingRaw: SQLite rejects HAVING without a
                    // GROUP BY, and the outstanding expression is a correlated
                    // subquery that belongs in WHERE. Verified against SQLite.
                    ->whereRaw("($charged - $allocated) > 0")
                    ->orderByRaw("($charged - $allocated) DESC")
            )
            ->columns([
                TextColumn::make('name')
                    ->label('العميل')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->fontFamily('mono')
                    ->color('gray'),
                TextColumn::make('outstanding_cents')
                    ->label('المتبقي')
                    ->weight('bold')
                    ->color('danger')
                    ->formatStateUsing(fn ($state): string => sprintf('$%d.%02d', intdiv((int) $state, 100), (int) $state % 100)),
            ])
            ->paginated(false);
    }
}
