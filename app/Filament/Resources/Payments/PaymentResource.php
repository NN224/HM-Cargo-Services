<?php

namespace App\Filament\Resources\Payments;

use App\Enums\Capability;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'المدفوعات';

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'المدفوعات';

    protected static string|UnitEnum|null $navigationGroup = 'العملاء والمال';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        // Reachable by anyone, so the screen appears in the navigation and can
        // render itself as locked. Whether the real list shows is decided in
        // ListPayments; recording a payment stays gated by canCreate().
        return auth()->user() instanceof User;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasCapability(Capability::RecordPayments);
    }

    public static function canEdit(Model $record): bool
    {
        // Payments are never edited. Reversals are handled via actions.
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        // Never hard-delete
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
        ];
    }
}
