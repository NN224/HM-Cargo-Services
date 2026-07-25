<?php
use App\Models\Batch;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Livewire\Component;

$select = Select::make('batch_id')
    ->options(function () {
        return [
            '1' => 'Batch 1',
            '__CREATE__' => '➕ إضافة رحلة جديدة',
        ];
    })
    ->live()
    ->afterStateUpdated(function ($set, $state, $livewire) {
        if ($state === '__CREATE__') {
            $set('batch_id', null);
            $livewire->mountFormComponentAction('data.batch_id', 'createOption');
        }
    })
    ->createOptionForm([]);

echo "Select created.\n";
