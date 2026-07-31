<?php

namespace App\Http\Controllers;

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public JSON API for shipment tracking.
 *
 * Consumed by hmcargoservices.com (the marketing site / PWA).
 * Exposes exactly the same safe projection as TrackingController:
 * no profit, no cost, no employee data, no other-account data.
 *
 * Two entry points:
 *   GET /api/track/{token}        — unguessable 48-char public token
 *   GET /api/track/ref/{reference} — human-readable reference (HM-2026-000001)
 */
class TrackingApiController extends Controller
{
    public function show(string $token): JsonResponse
    {
        return $this->resolve($token, byToken: true);
    }

    public function showByReference(string $reference): JsonResponse
    {
        // Look up the token from the reference, then resolve normally.
        $row = DB::table('shipments')
            ->where('reference', strtoupper(trim($reference)))
            ->select('public_token')
            ->first();

        if (! $row) {
            return response()->json(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        return $this->resolve($row->public_token, byToken: true);
    }

    // -------------------------------------------------------------------------

    private function resolve(string $token, bool $byToken): JsonResponse
    {
        $shipment = DB::table('shipments as shipments')
            ->leftJoin('batches', 'batches.id', '=', 'shipments.batch_id')
            ->leftJoin('routes', 'routes.id', '=', 'batches.route_id')
            ->leftJoin('warehouses as origin_warehouses', 'origin_warehouses.id', '=', 'routes.origin_warehouse_id')
            ->where('shipments.public_token', $token)
            ->select([
                'shipments.reference',
                'shipments.recipient_name',
                'shipments.status',
                'shipments.id',
                'shipments.final_charge_cents',
                'routes.name as route_name',
                'routes.origin_airport_name',
                'routes.destination_airport_name',
                'routes.delivery_office_name',
                'origin_warehouses.name as origin_warehouse_name',
            ])
            ->selectRaw('CAST(shipments.total_weight_kg AS TEXT) as total_weight_kg')
            ->first();

        if (! $shipment) {
            // If the token looks like a barcode, try resolving to the parent shipment token
            $parent = DB::table('packages')
                ->join('shipments', 'shipments.id', '=', 'packages.shipment_id')
                ->where('packages.barcode', $token)
                ->select('shipments.public_token', 'shipments.reference')
                ->first();

            if ($parent) {
                return $this->resolve($parent->public_token, byToken: true);
            }

            return response()->json(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        $progress = DB::table('packages')
            ->where('shipment_id', $shipment->id)
            ->where('status', '!=', PackageStatus::Cancelled->value)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as arrived_count',
                [PackageStatus::ArrivedDestination->value, PackageStatus::Collected->value],
            )
            ->first();

        $paidCents = (int) DB::table('payment_allocations')
            ->where('shipment_id', $shipment->id)
            ->sum('amount_cents');

        $journeyPackages = DB::table('packages')
            ->where('shipment_id', $shipment->id)
            ->whereNotIn('status', [PackageStatus::Cancelled->value])
            ->select(['status', 'is_delayed'])
            ->get();

        $publishedEvents = DB::table('package_status_events as events')
            ->join('packages', 'packages.id', '=', 'events.package_id')
            ->where('packages.shipment_id', $shipment->id)
            ->whereNotNull('events.public_reason')
            ->where('events.public_reason', '!=', '')
            ->orderBy('events.scanned_at')
            ->select(['events.status', 'events.public_reason', 'events.scanned_at'])
            ->get()
            ->map(fn ($event): array => [
                'label' => PackageStatus::tryFrom($event->status)?->label() ?? 'تحديث رحلة',
                'reason' => $event->public_reason,
                'occurred_at' => date('Y-m-d H:i', strtotime($event->scanned_at)),
            ])
            ->all();

        $timeline = DB::table('package_status_events as events')
            ->join('packages', 'packages.id', '=', 'events.package_id')
            ->join('shipments', 'shipments.id', '=', 'packages.shipment_id')
            ->join('warehouses', 'warehouses.id', '=', 'events.warehouse_id')
            ->where('shipments.id', $shipment->id)
            ->whereNotIn('events.status', [
                PackageStatus::InTransit->value,
                PackageStatus::DepartedTransit->value,
            ])
            ->orderBy('events.scanned_at')
            ->select(['events.status', 'events.scanned_at', 'warehouses.name as warehouse_name'])
            ->get()
            ->map(fn ($event): array => [
                'label' => $this->publicEventLabel($event->status, $event->warehouse_name),
                'occurred_at' => date('Y-m-d H:i', strtotime($event->scanned_at)),
            ])
            ->all();

        $finalCents = $shipment->final_charge_cents === null
            ? null
            : (int) $shipment->final_charge_cents;
        $remainingCents = $finalCents === null ? null : max(0, $finalCents - $paidCents);
        $totalCount = (int) ($progress->total_count ?? 0);
        $arrivedCount = (int) ($progress->arrived_count ?? 0);

        return response()->json([
            'reference' => $shipment->reference,
            'recipient' => $this->maskRecipient($shipment->recipient_name),
            'route' => $shipment->route_name ?? null,
            'status' => ShipmentStatus::tryFrom($shipment->status)?->value,
            'status_label' => ShipmentStatus::tryFrom($shipment->status)?->label() ?? 'قيد المتابعة',
            'progress' => [
                'arrived' => $arrivedCount,
                'total' => $totalCount,
            ],
            'financial' => [
                'final_charge' => $finalCents,
                'paid' => $finalCents === null ? null : $paidCents,
                'remaining' => $remainingCents,
                'payment_status' => $this->paymentStatus($finalCents, $paidCents),
            ],
            'journey' => $this->journeyProjection($shipment, $journeyPackages),
            'published_events' => $publishedEvents,
            'timeline' => $timeline,
        ]);

    }

    // -------------------------------------------------------------------------

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

    private function publicEventLabel(string $status, string $warehouse): string
    {
        return match (PackageStatus::tryFrom($status)) {
            PackageStatus::ReceivedOrigin => 'تم استلام طرد في مستودع المنشأ',
            PackageStatus::ArrivedOriginAirport,
            PackageStatus::InTransit => 'وصل طرد إلى مطار الانطلاق',
            PackageStatus::ArrivedTransit,
            PackageStatus::DepartedTransit,
            PackageStatus::ArrivedDestination => "وصل طرد إلى مستودع {$warehouse}",
            PackageStatus::Collected => 'تم تسليم طرد',
            PackageStatus::Cancelled => 'أُلغي طرد من الشحنة',
            PackageStatus::Missing => 'يوجد طرد يحتاج متابعة',
            PackageStatus::Damaged => 'يوجد طرد يحتاج مراجعة',
            default => 'تم تحديث حالة طرد',
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object{status: string, is_delayed: bool|int}>  $packages
     */
    private function journeyProjection(object $shipment, $packages): array
    {
        $journeyPackages = $packages
            ->filter(fn ($p): bool => PackageStatus::tryFrom($p->status)?->journeyPosition() !== null)
            ->values();

        $steps = [];

        foreach (PackageStatus::journeySteps() as $position => $status) {
            $atStep = $journeyPackages->filter(
                fn ($p): bool => PackageStatus::from($p->status)->journeyPosition() === $position
            );

            $steps[] = [
                'status' => $status->value,
                'label' => $this->journeyLabel($shipment, $status),
                'completed_count' => $journeyPackages
                    ->filter(fn ($p): bool => PackageStatus::from($p->status)->journeyPosition() > $position)
                    ->count(),
                'current_count' => $atStep->count(),
                'delayed_count' => $atStep->filter(fn ($p): bool => (bool) $p->is_delayed)->count(),
            ];
        }

        return [
            'steps' => $steps,
            'package_count' => $journeyPackages->count(),
            'delayed_count' => $journeyPackages->filter(fn ($p): bool => (bool) $p->is_delayed)->count(),
        ];
    }

    private function journeyLabel(object $shipment, PackageStatus $status): string
    {
        return match ($status) {
            PackageStatus::ReceivedOrigin => 'وصل مستودع '.($shipment->origin_warehouse_name ?? 'المنشأ'),
            PackageStatus::ArrivedOriginAirport,
            PackageStatus::InTransit => 'وصل '.($shipment->origin_airport_name ?? 'مطار الانطلاق'),
            PackageStatus::ArrivedTransit,
            PackageStatus::DepartedTransit => 'وصل '.($shipment->destination_airport_name ?? 'مطار الوصول'),
            PackageStatus::ArrivedDestination => 'وصل '.($shipment->delivery_office_name ?? 'مستودع الوصول'),
            PackageStatus::Collected => 'استلمه العميل',
            default => 'غير مضبوط',
        };
    }

    private function paymentStatus(?int $finalCents, int $paidCents): string
    {
        if ($finalCents === null) {
            return 'pending';
        }

        if ($paidCents <= 0) {
            return 'unpaid';
        }

        return $paidCents >= $finalCents ? 'paid' : 'partial';
    }
}
