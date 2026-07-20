<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المستودع')
                    ->required()
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'auto'])
                    ->unique(ignoreRecord: true),
                TextInput::make('location')
                    ->label('الموقع')
                    ->required()
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'auto']),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->helperText('المستودعات تُعطَّل ولا تُحذف نهائياً.')
                    ->default(true),
            ]);
    }
}
