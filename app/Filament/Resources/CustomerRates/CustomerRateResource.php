<?php

namespace App\Filament\Resources\CustomerRates;

use App\Enums\Capability;
use App\Filament\Resources\CustomerRates\Pages\CreateCustomerRate;
use App\Filament\Resources\CustomerRates\Pages\EditCustomerRate;
use App\Filament\Resources\CustomerRates\Pages\ListCustomerRates;
use App\Filament\Resources\CustomerRates\Schemas\CustomerRateForm;
use App\Filament\Resources\CustomerRates\Tables\CustomerRatesTable;
use App\Models\CustomerRate;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomerRateResource extends Resource
{
    protected static ?string $model = CustomerRate::class;

    /**
     * A rate per kilogram is money (D-024). This screen is hidden from the
     * navigation — rates read on the customer page — but its routes stay
     * registered so that page's edit link resolves. Without this gate a
     * direct visit to /admin/customer-rates showed every customer's rate to
     * any employee. The customers capability is required to reach it at all.
     */
    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->hasCapability(Capability::ManageCustomers);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'أسعار العملاء';

    protected static ?string $modelLabel = 'سعر عميل';

    protected static ?string $pluralModelLabel = 'أسعار العملاء';

    protected static string|UnitEnum|null $navigationGroup = 'العملاء والمال';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CustomerRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerRatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * A rate is read on its customer's page. Its own pages stay registered —
     * changing a rate still happens there, behind the confirmation that names
     * the old and new figures — but it is no longer a destination of its own.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerRates::route('/'),
            'create' => CreateCustomerRate::route('/create'),
            'edit' => EditCustomerRate::route('/{record}/edit'),
        ];
    }
}
