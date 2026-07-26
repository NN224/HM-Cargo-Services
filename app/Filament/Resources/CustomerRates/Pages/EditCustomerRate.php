<?php

namespace App\Filament\Resources\CustomerRates\Pages;

use App\Filament\Resources\CustomerRates\CustomerRateResource;
use App\Models\CustomerRate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property CustomerRate $record
 */
class EditCustomerRate extends EditRecord
{
    protected static string $resource = CustomerRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Filament's default Save button is a native `<form>` submit button
     * (see `EditRecord::hasFormWrapper()`/`getFormContentComponent()`): its
     * `wire:click` is disabled in favour of the surrounding `<form
     * wire:submit="save">`, so `requiresConfirmation()` alone never gates
     * it — clicking Save, or pressing Enter in any field, calls save()
     * directly. Removing the form wrapper forces both paths through the
     * Livewire action system instead, where confirmation is actually
     * enforced before save() runs.
     */
    public function hasFormWrapper(): bool
    {
        return false;
    }

    /**
     * The confirmation modal must be the last gate, not the first: an
     * operator who types an invalid rate should see the field error
     * immediately, not be asked "change 3.00 to X?" and only learn it was
     * invalid after confirming. Left alone, mounting the "save" action here
     * just opens the modal — the form's own validation (required,
     * minValue(0.01), ...) doesn't run until save() executes inside
     * callMountedAction(), i.e. after the operator has already confirmed.
     *
     * Running the same validation the form already performs (via
     * $this->form->getState(), the identical call save() makes) before
     * mounting proceeds means an invalid value throws here instead: Livewire
     * turns that into the usual inline field error, and — because an
     * exception aborts this method — the parent mountAction() below never
     * runs, so the confirmation action never mounts and no modal appears.
     */
    public function mountAction(string $name, array $arguments = [], array $context = []): mixed
    {
        if ($name === 'save') {
            $this->form->getState();
        }

        return parent::mountAction($name, $arguments, $context);
    }

    /**
     * A rate is a standing agreement, not a per-load figure: a slipped
     * decimal here does not spoil one shipment, it becomes the customer's
     * price until somebody notices. Confirming names both figures so the
     * mistake is visible before it is saved.
     */
    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            // With hasFormWrapper() false, the parent already sets
            // ->action('save') (a plain string). A string action is
            // returned verbatim as the click handler and calls save()
            // directly, bypassing confirmation — so it must be a Closure
            // here, which forces the click handler to `mountAction('save')`
            // and makes `requiresConfirmation()` take effect.
            ->action(fn () => $this->save())
            ->requiresConfirmation()
            ->modalHeading('تغيير سعر متفق عليه')
            ->modalDescription(function (): string {
                $old = number_format($this->record->rate_per_kg_cents / 100, 2);

                // The form's TextInput holds dollars in its live state; the
                // cents conversion (dehydrateStateUsing) only runs inside
                // save()'s form->getState() call, which happens after this
                // description is rendered. So the pending value here is
                // still a dollar amount, not cents — do not divide by 100.
                $new = number_format((float) ($this->data['rate_per_kg_cents'] ?? 0), 2);

                return "سعر {$this->record->customer->name} على {$this->record->route->name} كان \${$old} — هل تريد تغييره إلى \${$new}؟";
            });
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
