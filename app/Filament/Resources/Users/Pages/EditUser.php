<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Capability;
use App\Enums\LockablePage;
use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $capabilities = [];

        foreach (Capability::cases() as $capability) {
            $key = "capability_{$capability->value}";
            if (! empty($data[$key])) {
                $capabilities[] = $capability->value;
            }
            unset($data[$key]);
        }

        if (isset($data['role']) && $data['role'] === UserRole::Administrator->value) {
            $data['capabilities'] = [];
            $data['warehouse_id'] = null;
        } else {
            $data['capabilities'] = $capabilities;
        }

        $locked = [];
        foreach (LockablePage::cases() as $page) {
            $key = "lock_{$page->value}";
            if (! empty($data[$key])) {
                $locked[] = $page->value;
            }
            unset($data[$key]);
        }
        // An administrator is never locked; keep the column clean for them.
        $data['locked_pages'] = ($data['role'] ?? null) === UserRole::Administrator->value ? [] : $locked;

        return $data;
    }
}
