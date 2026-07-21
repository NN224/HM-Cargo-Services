<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $capabilities = [];

        // Extract the explicit capability toggles and build the capabilities array.
        foreach (Capability::cases() as $capability) {
            $key = "capability_{$capability->value}";
            if (! empty($data[$key])) {
                $capabilities[] = $capability->value;
            }
            // Remove the temporary toggle key from the saved data.
            unset($data[$key]);
        }

        // Administrators get an empty list in DB because they have them implicitly.
        if (isset($data['role']) && $data['role'] === UserRole::Administrator->value) {
            $data['capabilities'] = [];
            $data['warehouse_id'] = null; // Clean up just in case
        } else {
            $data['capabilities'] = $capabilities;
        }

        return $data;
    }
}
