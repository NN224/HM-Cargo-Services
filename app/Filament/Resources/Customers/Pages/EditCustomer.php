<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Enums\Capability;
use App\Filament\Resources\CustomerRates\CustomerRateResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\CustomerRate;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        // No delete action: records are deactivated, never hard-deleted.
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                $this->getRelationManagersContentComponent(),
                ...$this->ratesSection(),
            ]);
    }

    /**
     * A customer's agreed prices, read-only.
     *
     * Read here and changed on the rate's own page: the confirmation that
     * names the old and new figures fires only through that page's rebuilt
     * save action, and it fails silently when it breaks.
     *
     * @return array<int, Component>
     */
    private function ratesSection(): array
    {
        $user = auth()->user();

        // The rate per kilogram is money (D-024). An employee without the
        // customers capability still needs the rest of this page to receive
        // cargo, so the section is omitted rather than the page locked.
        if (! $user instanceof User || ! $user->hasCapability(Capability::ManageCustomers)) {
            return [];
        }

        $rates = $this->record->rates()->with('route')->get();

        return [
            Section::make('الأسعار المتفق عليها')
                ->description('سعر هذا العميل على كل مسار. لتغيير سعر، افتحه من زر التعديل.')
                ->schema([
                    RepeatableEntry::make('rates')
                        ->label('')
                        ->state($rates->map(fn (CustomerRate $rate): array => [
                            'route' => $rate->route->name,
                            'rate' => number_format($rate->rate_per_kg_cents / 100, 2),
                            'url' => CustomerRateResource::getUrl('edit', ['record' => $rate]),
                        ])->all())
                        ->schema([
                            TextEntry::make('route')->label('المسار'),
                            TextEntry::make('rate')->label('السعر لكل كيلو (دولار)'),
                            TextEntry::make('url')
                                ->label('')
                                ->formatStateUsing(fn (): string => 'تعديل السعر')
                                ->url(fn ($state): string => $state),
                        ])
                        ->columns(3)
                        ->visible($rates->isNotEmpty()),

                    TextEntry::make('no_rates')
                        ->label('')
                        ->state('لا توجد أسعار متفق عليها مع هذا العميل بعد.')
                        ->visible($rates->isEmpty()),
                ]),
        ];
    }

    /**
     * Return to the list after saving.
     *
     * Filament's defaults send a new record to its own edit form and leave a
     * saved record where it is, which reads as "nothing happened" to an
     * operator working through a queue of records.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
