<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\Capability;
use App\Filament\Concerns\LocksWhenUnauthorized;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListPayments extends ListRecords
{
    use LocksWhenUnauthorized;

    protected static string $resource = PaymentResource::class;

    public function content(Schema $schema): Schema
    {
        $user = auth()->user();

        // The resource itself is reachable by any authenticated user (see
        // PaymentResource::canViewAny), so the capability check that used to
        // gate the whole page now gates only what the page renders: the
        // capability holder (or an administrator) sees the real list, anyone
        // else sees the lock notice instead of the table and its columns.
        if (! $user instanceof User || ! $user->hasCapability(Capability::RecordPayments)) {
            return $schema->components([
                $this->lockNotice($this->capabilityLockReason(Capability::RecordPayments)),
            ]);
        }

        return parent::content($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
