<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Resources\Batches\BatchResource;
use App\Models\Route;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $route = Route::find($data['route_id'] ?? null);

        if (! $route || ! BatchResource::canUseRoute($route)) {
            throw ValidationException::withMessages([
                'data.route_id' => 'لا يمكنك إنشاء رحلة على مسار لا يمر عبر مستودعك.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
