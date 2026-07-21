<?php

namespace App\Filament\Resources\Batches\Schemas;

use App\Filament\Resources\Batches\BatchResource;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل الرحلة')
                    ->schema([
                        TextEntry::make('reference')
                            ->label('رقم الرحلة')
                            ->visibleOn('view'),

                        Select::make('route_id')
                            ->label('المسار')
                            ->relationship(
                                'route',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => BatchResource::scopeRouteQuery($query),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextEntry::make('status')
                            ->label('الحالة')
                            ->formatStateUsing(fn ($state): string => $state?->label() ?? '—')
                            ->visibleOn('view'),

                        TextEntry::make('dispatched_on')
                            ->label('تاريخ الإرسال')
                            ->date('Y-m-d')
                            ->placeholder('لم تُرسل بعد')
                            ->visibleOn('view'),
                    ])
                    ->columns(2),
            ]);
    }
}
