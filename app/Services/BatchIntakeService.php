<?php

namespace App\Services;

use App\Enums\Capability;
use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
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

            $this->recordAgreedRateIfMissing($customer, $batch, $data, $actor);

            [$name, $phone] = $this->resolveRecipient($customer, $data);

            $shipment = Shipment::create([
                'customer_id' => $customer->id,
                'recipient_name' => $name,
                'recipient_phone' => $phone,
                // The operator is standing inside a batch bound somewhere
                // specific. Asking them to retype that destination only
                // creates a chance to contradict it.
                'destination_warehouse_id' => $batch->route->destination_warehouse_id,
            ]);

            foreach ($packages as $package) {
                $shipment->packages()->create([
                    'weight_kg' => $package['weight_kg'],
                    'description' => $package['description'] ?? null,
                ]);
            }

            $shipment->recalculateTotalWeight();

            // Pricing stays where it has always been. If the customer has no
            // agreed rate for this route, assign() refuses and this whole
            // transaction unwinds — no half-received cargo.
            return $this->assignment->assign($shipment->fresh(), $batch);
        });
    }

    /**
     * A warehouse employee operates only their assigned warehouse (AGENTS.md).
     * A batch's route is relevant at its origin, transit and destination —
     * the same rule BatchResource already applies to the batches list and to
     * everything else batch-scoped, reused here via canUseRoute() rather than
     * a parallel rule.
     *
     * The Filament page only offers a scoped list of batches, and Filament's
     * own Select validation rejects a tampered id against that same list —
     * but neither of those is authorization (AGENTS.md is explicit that it
     * belongs in a backend guard). $actor is checked directly here so a
     * caller reaching this service by any path other than the page, with any
     * batch id, is refused identically.
     *
     * @throws DomainException when the actor's warehouse has no part in the
     *                          batch's route
     */
    private function guardActorCanUseBatch(Batch $batch, User $actor): void
    {
        if (BatchResource::canUseRoute($batch->route, $actor)) {
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

    /**
     * Record a first agreed rate, when one was supplied and none exists.
     *
     * A customer with no rate for this route is one nobody has settled terms
     * with yet. The system still refuses to invent a figure — but a user
     * entitled to record the agreement may do it here rather than break off
     * to another screen mid-intake.
     *
     * An existing rate is never touched. Changing an agreed price belongs on
     * the rates screen behind its confirmation, not to a side effect of
     * receiving boxes.
     *
     * The Filament form only shows this field to a user holding
     * Capability::ManageCustomers (see ReceiveIntoBatch::needsAgreedRate()),
     * but that is a UI convenience, not authorization — AGENTS.md is explicit
     * that authorization belongs in backend guards, never in hiding a field.
     * A crafted Livewire property update could otherwise reach this method
     * with a value for a user never entitled to set it, so the check is
     * repeated here regardless of how the value arrived.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException when the acting user may not set a rate, or
     *                          when the supplied value is invalid
     */
    private function recordAgreedRateIfMissing(Customer $customer, Batch $batch, array $data, User $actor): void
    {
        $agreed = $data['agreed_rate_per_kg_cents'] ?? null;

        if ($agreed === null) {
            return;
        }

        if ($customer->rateForRoute($batch->route) !== null) {
            return;
        }

        // An administrator holds every capability implicitly (D-022); the
        // check below reflects that automatically via hasCapability().
        if (! $actor->hasCapability(Capability::ManageCustomers)) {
            throw new DomainException('لا يملك المستخدم صلاحية إدارة العملاء، ولا يمكنه تسجيل سعر متفق عليه.');
        }

        if ((int) $agreed <= 0) {
            throw new DomainException('سعر الكيلو المتفق عليه يجب أن يكون أكبر من صفر.');
        }

        CustomerRate::create([
            'customer_id' => $customer->id,
            'route_id' => $batch->route_id,
            'rate_per_kg_cents' => (int) $agreed,
        ]);
    }
}
