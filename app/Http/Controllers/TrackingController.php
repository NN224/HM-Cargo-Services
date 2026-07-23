<?php

namespace App\Http\Controllers;

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackingController extends Controller
{
    public function show(string $token): View|RedirectResponse|HttpResponse
    {
        $shipment = DB::table('shipments as shipments')
            ->leftJoin('batches', 'batches.id', '=', 'shipments.batch_id')
            ->leftJoin('routes', 'routes.id', '=', 'batches.route_id')
            ->where('shipments.public_token', $token)
            ->select([
                'shipments.reference',
                'shipments.recipient_name',
                'shipments.status',
                'shipments.final_charge_cents',
                'routes.name as route_name',
            ])
            // Both SQLite and PostgreSQL support TEXT. Forcing the decimal to
            // text prevents PDO from turning an exact weight into a float.
            ->selectRaw('CAST(shipments.total_weight_kg AS TEXT) as total_weight_kg')
            ->first();

        if (! $shipment) {
            $parent = DB::table('packages')
                ->join('shipments', 'shipments.id', '=', 'packages.shipment_id')
                ->where('packages.barcode', $token)
                ->select('shipments.public_token')
                ->first();

            if ($parent) {
                return redirect()->route('tracking.show', $parent->public_token);
            }

            // Invalid shapes and well-formed-but-unknown values deliberately
            // share one body so the public endpoint reveals no lookup facts.
            return response()
                ->view('tracking.not-found', status: Response::HTTP_NOT_FOUND);
        }

        $progress = DB::table('packages')
            ->where('shipment_id', function ($query) use ($token): void {
                $query->from('shipments')
                    ->where('public_token', $token)
                    ->select('id');
            })
            ->where('status', '!=', PackageStatus::Cancelled->value)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as arrived_count',
                [PackageStatus::ArrivedDestination->value, PackageStatus::Collected->value],
            )
            ->first();

        $paidCents = (int) DB::table('payment_allocations')
            ->where('shipment_id', function ($query) use ($token): void {
                $query->from('shipments')
                    ->where('public_token', $token)
                    ->select('id');
            })
            ->sum('amount_cents');

        $timeline = DB::table('package_status_events as events')
            ->join('packages', 'packages.id', '=', 'events.package_id')
            ->join('shipments', 'shipments.id', '=', 'packages.shipment_id')
            ->join('warehouses', 'warehouses.id', '=', 'events.warehouse_id')
            ->where('shipments.public_token', $token)
            ->orderBy('events.scanned_at')
            ->select([
                'events.status',
                'events.scanned_at',
                'warehouses.name as warehouse_name',
            ])
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

        // Only this projection crosses into Blade. IDs, raw contact data,
        // costs, rates, employee fields, notes and payment records never do.
        $tracking = [
            'reference' => $shipment->reference,
            'recipient' => $this->maskRecipient($shipment->recipient_name),
            'route' => $shipment->route_name ?? 'لم يُحدد المسار بعد',
            'stage' => ShipmentStatus::tryFrom($shipment->status)?->label() ?? 'قيد المتابعة',
            'progress' => sprintf(
                'وصل %s من %s',
                $this->arabicDigits($arrivedCount),
                $this->arabicDigits($totalCount),
            ),
            'total_weight' => $this->formatWeight($shipment->total_weight_kg).' كغ',
            'final_charge' => $this->formatMoney($finalCents),
            'paid_amount' => $this->formatMoney($finalCents === null ? null : $paidCents),
            'remaining_amount' => $this->formatMoney($remainingCents),
            'payment_status' => $this->paymentStatus($finalCents, $paidCents),
            'timeline' => $timeline,
            'qr' => app(\App\Services\QrCode::class)->svg(route('tracking.show', $token), 160),
        ];

        return view('tracking.show', compact('tracking'));
    }

    private function maskRecipient(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) === 1) {
            return mb_substr($parts[0] ?? '', 0, 1).'***';
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
            PackageStatus::Created => 'تم إنشاء طرد',
            PackageStatus::ReceivedOrigin, PackageStatus::Received => 'تم استلام طرد في مستودع المنشأ',
            PackageStatus::InTransit => 'طرد في الطريق',
            PackageStatus::ArrivedTransit,
            PackageStatus::ArrivedDestination,
            PackageStatus::Arrived => "وصل طرد إلى مستودع {$warehouse}",
            PackageStatus::DepartedTransit => 'غادر طرد مستودع العبور',
            PackageStatus::Collected => 'تم تسليم طرد',
            PackageStatus::Cancelled => 'أُلغي طرد من الشحنة',
            PackageStatus::Missing => 'يوجد طرد يحتاج متابعة',
            PackageStatus::Damaged => 'يوجد طرد يحتاج مراجعة',
            default => 'تم تحديث حالة طرد',
        };
    }

    private function formatMoney(?int $cents): string
    {
        if ($cents === null) {
            return 'غير متاح';
        }

        $absolute = abs($cents);

        return sprintf(
            '%s%d.%02d $',
            $cents < 0 ? '-' : '',
            intdiv($absolute, 100),
            $absolute % 100,
        );
    }

    private function formatWeight(string $weight): string
    {
        [$whole, $fraction] = array_pad(explode('.', $weight, 2), 2, '');

        return $whole.'.'.substr(str_pad($fraction, 4, '0'), 0, 4);
    }

    private function paymentStatus(?int $finalCents, int $paidCents): string
    {
        if ($finalCents === null) {
            return 'غير متاح';
        }

        if ($paidCents <= 0) {
            return 'غير مدفوع';
        }

        return $paidCents >= $finalCents ? 'مدفوع بالكامل' : 'مدفوع جزئياً';
    }

    private function arabicDigits(int $number): string
    {
        return strtr((string) $number, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]);
    }
}
