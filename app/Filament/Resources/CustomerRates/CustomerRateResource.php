<?php

namespace App\Filament\Resources\CustomerRates;

use App\Filament\Resources\CustomerRates\Pages\CreateCustomerRate;
use App\Filament\Resources\CustomerRates\Pages\EditCustomerRate;
use App\Filament\Resources\CustomerRates\Pages\ListCustomerRates;
use App\Filament\Resources\CustomerRates\Schemas\CustomerRateForm;
use App\Filament\Resources\CustomerRates\Tables\CustomerRatesTable;
use App\Models\CustomerRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerRateResource extends Resource
{
    protected static ?string $model = CustomerRate::class;

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

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerRates::route('/'),
            'create' => CreateCustomerRate::route('/create'),
            'edit' => EditCustomerRate::route('/{record}/edit'),
        ];
    }
}
