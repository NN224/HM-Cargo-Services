<?php

namespace App\Filament\Resources\Batches\Schemas;

use App\Filament\Resources\Batches\BatchResource;
use App\Models\Route;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                        TextInput::make('reference')
                            ->label('رقم/اسم الرحلة')
                            ->placeholder('مثال: BCH-101 أو اتركه فارغاً للتوليد التلقائي')
                            ->maxLength(255)
                            ->unique('batches', 'reference', ignoreRecord: true)
                            ->helperText('يمكنك إدخال رقم أو اسم للرحلة يدوياً، أو تركه فارغاً ليقوم النظام بتوليده تلقائياً.')
                            ->hiddenOn('view'),

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
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('اسم المسار')
                                    ->required()
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'auto'])
                                    ->unique('routes', 'name'),

                                Select::make('origin_warehouse_id')
                                    ->label('مستودع المنشأ')
                                    ->options(fn () => Warehouse::pluck('name', 'id')->all())
                                    ->required(),

                                Select::make('destination_warehouse_id')
                                    ->label('مستودع الوجهة')
                                    ->options(fn () => Warehouse::pluck('name', 'id')->all())
                                    ->required()
                                    ->different('origin_warehouse_id'),

                                Select::make('transit_warehouse_id')
                                    ->label('مستودع العبور (اختياري)')
                                    ->options(fn () => Warehouse::pluck('name', 'id')->all())
                                    ->different('origin_warehouse_id')
                                    ->different('destination_warehouse_id'),
                            ])
                            ->createOptionUsing(fn (array $data): int => Route::create($data)->id),

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
