<?php

namespace App\Filament\Resources\CustomerRates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('route_id')
                    ->label('المسار')
                    ->relationship('route', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('المسار المباشر والمسار عبر بيروت مساران مختلفان وقد يختلف سعرهما.'),

                // The operator types dollars; storage is integer cents.
                // Nobody should ever type a cent value into a form.
                TextInput::make('rate_per_kg_cents')
                    ->label('السعر لكل كيلوغرام (دولار)')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix('$')
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : number_format($state / 100, 2, '.', ''))
                    ->dehydrateStateUsing(fn (?string $state): int => (int) round(((float) $state) * 100)),
            ]);
    }
}
