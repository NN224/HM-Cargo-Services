<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Shipment;
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
     *
     * @throws DomainException when the intake cannot be completed
     */
    public function receive(Batch $batch, array $data): Shipment
    {
        return DB::transaction(function () use ($batch, $data): Shipment {
            $packages = $data['packages'] ?? [];

            if ($packages === []) {
                throw new DomainException('أضف طرداً واحداً على الأقل قبل الحفظ.');
            }

            $customer = Customer::findOrFail($data['customer_id']);

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
