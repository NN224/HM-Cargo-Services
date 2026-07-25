<?php

namespace App\Filament\Resources\Routes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RouteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المسار')
                    ->required()
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'auto'])
                    ->unique(ignoreRecord: true)
                    ->helperText('مثال: دبي ← سوريا (مباشر) أو دبي ← بيروت ← سوريا'),

                Select::make('origin_warehouse_id')
                    ->label('مستودع المنشأ')
                    ->relationship('originWarehouse', 'name')
                    ->required(),

                Select::make('destination_warehouse_id')
                    ->label('مستودع الوجهة')
                    ->relationship('destinationWarehouse', 'name')
                    ->required()
                    ->different('origin_warehouse_id'),

                Select::make('transit_warehouse_id')
                    ->label('مستودع العبور (اختياري)')
                    ->relationship('transitWarehouse', 'name')
                    ->different('origin_warehouse_id')
                    ->different('destination_warehouse_id')
                    ->helperText('اتركه فارغاً للمسار المباشر. وجوده يجعل المسار مختلفاً وقد يختلف سعره.'),

                TextInput::make('origin_airport_name')
                    ->label('مطار الانطلاق')
                    ->required(fn (Get $get): bool => (bool) $get('is_active'))
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'auto']),

                TextInput::make('destination_airport_name')
                    ->label('مطار الوصول')
                    ->required(fn (Get $get): bool => (bool) $get('is_active'))
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'auto']),

                TextInput::make('delivery_office_name')
                    ->label('مكتب التسليم')
                    ->required(fn (Get $get): bool => (bool) $get('is_active'))
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'auto']),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }
}
