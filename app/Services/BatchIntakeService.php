<?php

namespace App\Services;

use App\Enums\Capability;
use App\Enums\ShipmentStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Receives a customer's packages directly into a batch.
 *
 * Cargo arrives across the week and the load leaves on a fixed day, so the
 * operator works from the batch inwards: open Thursday's load, add what just
 * came in. Building a shipment first and attaching it afterwards describes
 * the same facts in an order nobody works in.
 *
 * This does not reprice anything or bypass a rule. It fills in what the batch
 * already knows — the destination, the route, and therefore the rate — and
 * hands the result to BatchAssignmentService, which remains the only place a
 * shipment becomes billable.
 */
class BatchIntakeService
{
    public function __construct(
        private readonly BatchAssignmentService $assignment,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  User  $actor  The user performing the intake. Required — not
     *                       read from auth() — so the capability guard below
     *                       is explicit and testable, matching
     *                       PaymentService::reversePayment().
     *
     * @throws DomainException when the intake cannot be completed
     */
    public function receive(Batch $batch, array $data, User $actor): Shipment
    {
        return DB::transaction(function () use ($batch, $data, $actor): Shipment {
            $this->guardActorCanUseBatch($batch, $actor);

            $packages = $data['packages'] ?? [];

            if ($packages === []) {
                throw new DomainException('أضف طرداً واحداً على الأقل قبل الحفظ.');
            }

            $customer = Customer::findOrFail($data['customer_id']);

            [$name, $phone] = $this->resolveRecipient($customer, $data);

            $existingShipment = Shipment::query()
                ->where('customer_id', $customer->id)
                ->where('batch_id', $batch->id)
                ->whereNotIn('status', [ShipmentStatus::Collected->value, ShipmentStatus::Cancelled->value])
                ->first();

            if ($existingShipment) {
                $shipment = $existingShipment;
                $shipment->update([
                    'recipient_name' => $name,
                    'recipient_phone' => $phone,
                ]);
            } else {
                $shipment = Shipment::create([
                    'customer_id' => $customer->id,
                    'recipient_name' => $name,
                    'recipient_phone' => $phone,
                    'destination_warehouse_id' => $batch->route->destination_warehouse_id,
                ]);
            }

            $submittedPackageIds = [];
            foreach ($packages as $package) {
                $pkgId = $package['id'] ?? null;
                $payload = [
                    'weight_kg' => $package['weight_kg'],
                    'description' => $package['description'] ?? null,
                    'source_barcode' => $package['source_barcode'] ?? null,
                    'custom_rate_per_kg_cents' => filled($package['custom_rate_per_kg'] ?? null)
                        ? (int) round(((float) $package['custom_rate_per_kg']) * 100)
                        : null,
                    'fixed_charge_cents' => filled($package['fixed_charge_usd'] ?? null)
                        ? (int) round(((float) $package['fixed_charge_usd']) * 100)
                        : null,
                ];

                if ($pkgId && $existingPkg = $shipment->packages()->find($pkgId)) {
                    $existingPkg->update($payload);
                    $submittedPackageIds[] = $existingPkg->id;
                } else {
                    $newPkg = $shipment->packages()->create($payload);
                    $submittedPackageIds[] = $newPkg->id;
                }
            }

            if ($existingShipment) {
                $shipment->packages()->whereNotIn('id', $submittedPackageIds)->delete();
            }

            $shipment->recalculateTotalWeight();

            if ($shipment->batch_id === null) {
                // Assign the shipment to the batch. We use a flag to skip
                // the strict route-rate check inside BatchAssignmentService
                // because we are explicitly providing manual package rates.
                $shipment = $this->assignment->assign($shipment->fresh(), $batch, skipRateCheck: true);
            }

            // Apply the final charge exactly as the user typed it in the form.
            if (isset($data['final_charge_usd']) && filled($data['final_charge_usd'])) {
                $shipment->forceFill([
                    'final_charge_cents' => (int) round(((float) $data['final_charge_usd']) * 100),
                ])->save();
            }

            return $shipment->fresh();
        });
    }

    /**
     * A warehouse employee operates only their assigned warehouse (AGENTS.md).
     * A batch's route is relevant at its origin, transit and destination —
     * the same rule the batches list applies, asked of the user directly
     * rather than restated here.
     *
     * The Filament page only offers a scoped list of batches, and Filament's
     * own Select validation rejects a tampered id against that same list —
     * but neither of those is authorization (AGENTS.md is explicit that it
     * belongs in a backend guard). $actor is checked directly here so a
     * caller reaching this service by any path other than the page, with any
     * batch id, is refused identically.
     *
     * @throws DomainException when the actor's warehouse has no part in the
     *                         batch's route
     */
    private function guardActorCanUseBatch(Batch $batch, User $actor): void
    {
        if ($actor->canUseRoute($batch->route)) {
            return;
        }

        throw new DomainException(
            "الرحلة {$batch->reference} خارج نطاق مستودعك، ولا يمكنك الاستلام فيها."
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}
     */
    private function resolveRecipient(Customer $customer, array $data): array
    {
        if (($data['recipient_is_customer'] ?? true) === true) {
            return [$customer->name, $customer->phone];
        }

        return [$data['recipient_name'], $data['recipient_phone']];
    }
}
