<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Support\JourneyPlaces;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The single safe projection of a shipment for public consumption.
 *
 * The Blade tracking page and the JSON API both read from here. They used to
 * carry near-identical copies of this logic, and the copies had already
 * drifted: the same scan rendered under two different Arabic labels depending
 * on which one you opened. One source removes that class of bug rather than
 * fixing this instance of it.
 *
 * Only what this class returns may reach a public response. Internal ids,
 * costs, rates, employee fields, private notes, supplier barcodes and any
 * other shipment stay out by never being selected (AGENTS.md).
 */
final class PublicTrackingProjection
{
    /**
     * Build the projection for a public token, or null when nothing matches.
     *
     * @return array<string, mixed>|null
     */
    public function forToken(string $token): ?array
    {
        $shipment = $this->findShipment($token);

        if ($shipment === null) {
            return null;
        }

        $packages = $this->findPackages((int) $shipment->id);
        $events = $this->findEvents($packages->pluck('id')->all());

        $projected = $packages
            ->map(fn (object $package): array => $this->projectPackage(
                $shipment,
                $package,
                $events->get((int) $package->id, collect()),
            ))
            ->values()
            ->all();

        return [
            'reference' => $shipment->reference,
            'recipient' => $this->maskRecipient($shipment->recipient_name),
            'route' => $shipment->route_name,
            'status' => ShipmentStatus::tryFrom($shipment->status)?->value,
            'status_label' => ShipmentStatus::tryFrom($shipment->status)?->label() ?? 'قيد المتابعة',
            'total_weight_kg' => $shipment->total_weight_kg,
            'packages' => $projected,
            'package_count' => count($projected),
            'arrived_count' => $this->arrivedCount($packages),
            'progress_ratio' => $this->progressRatio($projected),
            'delayed_count' => $packages->filter(fn (object $p): bool => (bool) $p->is_delayed)->count(),
            'notices' => $this->notices($events),
            'journey' => $this->shipmentJourney($shipment, $projected),
        ];
    }

    /**
     * A package barcode printed on a label resolves to its parent shipment's
     * token, so scanning any box on a pallet opens the same page.
     */
    public function tokenForBarcode(string $barcode): ?string
    {
        $parent = DB::table('packages')
            ->join('shipments', 'shipments.id', '=', 'packages.shipment_id')
            ->where('packages.barcode', $barcode)
            ->select('shipments.public_token')
            ->first();

        return $parent?->public_token;
    }

    // -------------------------------------------------------------------------
    // Reading
    // -------------------------------------------------------------------------

    private function findShipment(string $token): ?object
    {
        return DB::table('shipments')
            ->leftJoin('batches', 'batches.id', '=', 'shipments.batch_id')
            ->leftJoin('routes', 'routes.id', '=', 'batches.route_id')
            ->leftJoin('warehouses as origin_warehouses', 'origin_warehouses.id', '=', 'routes.origin_warehouse_id')
            ->where('shipments.public_token', $token)
            ->select([
                'shipments.id',
                'shipments.reference',
                'shipments.recipient_name',
                'shipments.status',
                'routes.name as route_name',
                'routes.origin_airport_name',
                'routes.destination_airport_name',
                'routes.delivery_office_name',
                'origin_warehouses.name as origin_warehouse_name',
            ])
            // Both SQLite and PostgreSQL support TEXT. Forcing the decimal to
            // text prevents PDO from turning an exact weight into a float.
            ->selectRaw('CAST(shipments.total_weight_kg AS TEXT) as total_weight_kg')
            ->first();
    }

    /** @return Collection<int, object> */
    private function findPackages(int $shipmentId): Collection
    {
        return DB::table('packages')
            ->where('shipment_id', $shipmentId)
            ->where('status', '!=', PackageStatus::Cancelled->value)
            ->orderBy('id')
            /*
             * packages.delay_reason is deliberately not selected. When an
             * administrator publishes a delay reason, PackageJourneyService
             * writes it to the package row *and* to an event's public_reason.
             * The event is the one deliberate publication point, so reading
             * the column as well adds nothing and creates a second path by
             * which an unpublished reason could escape.
             */
            ->select([
                'id',
                'barcode',
                'description',
                'status',
                'is_delayed',
            ])
            ->selectRaw('CAST(weight_kg AS TEXT) as weight_kg')
            ->get();
    }

    /**
     * Every event for every package in one query, grouped in PHP. A query per
     * package would scale with the size of the shipment.
     *
     * @param  array<int, int>  $packageIds
     * @return Collection<int, Collection<int, object>>
     */
    private function findEvents(array $packageIds): Collection
    {
        if ($packageIds === []) {
            return collect();
        }

        return DB::table('package_status_events')
            ->whereIn('package_id', $packageIds)
            ->orderBy('scanned_at')
            ->orderBy('id')
            ->select(['package_id', 'status', 'scanned_at', 'public_reason'])
            ->get()
            ->groupBy(fn (object $event): int => (int) $event->package_id);
    }

    // -------------------------------------------------------------------------
    // Projecting
    // -------------------------------------------------------------------------

    /**
     * @param  Collection<int, object>  $events
     * @return array<string, mixed>
     */
    private function projectPackage(object $shipment, object $package, Collection $events): array
    {
        $status = PackageStatus::tryFrom($package->status);
        $stamps = $this->journeyStamps($events);
        $position = $this->currentPosition($status, $stamps);

        // Collection is both the last step and the end of the journey. Every
        // other step is somewhere the package is currently sitting; this one
        // is somewhere it has finished. Without the distinction the final dot
        // stays "in progress" forever and a delivered shipment never reads as
        // complete.
        $isFinished = $status === PackageStatus::Collected;

        return [
            'barcode' => $package->barcode,
            'description' => $package->description,
            'weight_kg' => $package->weight_kg,
            'status' => $status?->value,
            'status_label' => $status?->label() ?? 'قيد المتابعة',
            'stage_label' => $position === null
                ? 'لم تبدأ الرحلة بعد'
                : $this->journeyLabel($shipment, PackageStatus::journeySteps()[$position]),
            'position' => $position,
            'is_finished' => $isFinished,
            'is_delayed' => (bool) $package->is_delayed,
            'is_exception' => (bool) $status?->isException(),
            // The most recent reason an administrator chose to publish for
            // this package. A reason they kept private is stored only in
            // private_reason, which is never selected.
            'delay_reason' => $this->latestPublicReason($events),
            'journey' => $this->packageJourney($shipment, $position, $stamps, $isFinished),
        ];
    }

    /**
     * @param  Collection<int, object>  $events
     */
    private function latestPublicReason(Collection $events): ?string
    {
        return $events
            ->pluck('public_reason')
            ->filter(fn (?string $reason): bool => filled($reason))
            ->last();
    }

    /**
     * The first time this package reached each journey position.
     *
     * A rescan must not move a timestamp forward, so the earliest event at a
     * position wins. Events are already ordered oldest first.
     *
     * @param  Collection<int, object>  $events
     * @return array<int, string>
     */
    private function journeyStamps(Collection $events): array
    {
        $stamps = [];

        foreach ($events as $event) {
            $position = PackageStatus::tryFrom($event->status)?->journeyPosition();

            if ($position === null || isset($stamps[$position])) {
                continue;
            }

            $stamps[$position] = $event->scanned_at;
        }

        return $stamps;
    }

    /**
     * Where the package sits on the five-step journey.
     *
     * A missing, damaged, newly created or retired-status package has no
     * journey position of its own, so its last recorded position is used
     * instead. Without that fallback an exception package would appear to
     * fall back to the start of the route.
     *
     * @param  array<int, string>  $stamps
     */
    private function currentPosition(?PackageStatus $status, array $stamps): ?int
    {
        $position = $status?->journeyPosition();

        if ($position !== null) {
            return $position;
        }

        return $stamps === [] ? null : max(array_keys($stamps));
    }

    /**
     * @param  array<int, string>  $stamps
     * @return array<int, array<string, mixed>>
     */
    private function packageJourney(object $shipment, ?int $position, array $stamps, bool $isFinished): array
    {
        $steps = [];

        foreach (PackageStatus::journeySteps() as $index => $status) {
            $steps[] = [
                'status' => $status->value,
                'label' => $this->journeyLabel($shipment, $status),
                'state' => match (true) {
                    $position === null => 'upcoming',
                    $index < $position => 'done',
                    $index === $position => $isFinished ? 'done' : 'current',
                    default => 'upcoming',
                },
                'occurred_at' => $stamps[$index] ?? null,
            ];
        }

        return $steps;
    }

    /**
     * The shipment-level stepper, counted from the already-projected packages
     * so that it can never disagree with the rows beneath it.
     *
     * @param  array<int, array<string, mixed>>  $packages
     * @return array<int, array<string, mixed>>
     */
    private function shipmentJourney(object $shipment, array $packages): array
    {
        $steps = [];

        foreach (PackageStatus::journeySteps() as $index => $status) {
            // A package sits at a step until it moves on — except a collected
            // one, which has finished its last step rather than stopped on it.
            $atStep = array_filter(
                $packages,
                fn (array $package): bool => $package['position'] === $index && ! $package['is_finished'],
            );

            $steps[] = [
                'status' => $status->value,
                'label' => $this->journeyLabel($shipment, $status),
                'completed_count' => count(array_filter(
                    $packages,
                    fn (array $package): bool => $this->completedThrough($package) >= $index,
                )),
                'current_count' => count($atStep),
                'delayed_count' => count(array_filter(
                    $atStep,
                    fn (array $package): bool => $package['is_delayed'],
                )),
            ];
        }

        return $steps;
    }

    /**
     * The last step index this package is finished with, or -1 for one that
     * has not cleared any step yet.
     *
     * @param  array<string, mixed>  $package
     */
    private function completedThrough(array $package): int
    {
        if ($package['position'] === null) {
            return -1;
        }

        return $package['is_finished'] ? $package['position'] : $package['position'] - 1;
    }

    /**
     * Public delay notices, deduplicated across the shipment so that ten
     * packages held by one flight do not print the same sentence ten times.
     *
     * @param  Collection<int, Collection<int, object>>  $events
     * @return array<int, string>
     */
    private function notices(Collection $events): array
    {
        return $events
            ->flatten(1)
            ->pluck('public_reason')
            ->filter(fn (?string $reason): bool => filled($reason))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * How far along the route the shipment is, as a fraction of the whole
     * journey — not the arrived count.
     *
     * The two differ for most of a shipment's life: a box sitting at the
     * origin airport has arrived nowhere yet, and a bar drawn from the
     * arrived count reads as "nothing has happened" while it is in the air.
     *
     * @param  array<int, array<string, mixed>>  $packages
     */
    private function progressRatio(array $packages): float
    {
        if ($packages === []) {
            return 0.0;
        }

        $steps = count(PackageStatus::journeySteps());

        // Position, not completedThrough: reaching a step is the achievement
        // here ("وصلت مطار دبي" has happened), whereas the stepper's green
        // fill asks the stricter question of whether the package has left.
        $reached = array_sum(array_map(
            fn (array $package): int => ($package['position'] ?? -1) + 1,
            $packages,
        ));

        return round($reached / (count($packages) * $steps), 4);
    }

    /** @param Collection<int, object> $packages */
    private function arrivedCount(Collection $packages): int
    {
        return $packages
            ->filter(fn (object $package): bool => in_array($package->status, [
                PackageStatus::ArrivedDestination->value,
                PackageStatus::Collected->value,
            ], true))
            ->count();
    }

    // -------------------------------------------------------------------------
    // Labels
    // -------------------------------------------------------------------------

    /**
     * The customer reads exactly the wording the administrator moves the
     * shipment through on the shipments screen — same class, one source.
     */
    private function journeyLabel(object $shipment, PackageStatus $status): string
    {
        return (new JourneyPlaces(
            originWarehouse: $shipment->origin_warehouse_name,
            originAirport: $shipment->origin_airport_name,
            destinationAirport: $shipment->destination_airport_name,
            deliveryOffice: $shipment->delivery_office_name,
        ))->labelFor($status);
    }

    /**
     * The recipient's own name confirms they opened the right link; the rest
     * is masked so a forwarded link does not hand over a full identity.
     */
    private function maskRecipient(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) === 1) {
            return mb_substr($parts[0], 0, 1).'***';
        }

        return implode(' ', array_map(
            fn (string $part, int $index): string => $index === 0
                ? $part
                : mb_substr($part, 0, 1).'***',
            $parts,
            array_keys($parts),
        ));
    }
}
