<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'المستودعات';

    protected static ?string $modelLabel = 'مستودع';

    protected static ?string $pluralModelLabel = 'المستودعات';

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 2;

    /**
     * Scope every listing to what the signed-in user may see.
     *
     * The policy authorizes individual records; without this an employee's
     * table query would still return every warehouse.
     *
     * @return Builder<Warehouse>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Warehouse> $query */
        $query = parent::getEloquentQuery();

        return $query->visibleTo(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
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
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
