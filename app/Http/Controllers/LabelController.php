<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
    /**
     * Print a label for a single package.
     */
    public function printPackage(Request $request, Package $package)
    {
        Gate::authorize('view', $package->shipment);

        $package->loadMissing([
            'shipment.customer',
            'shipment.batch.route.destinationWarehouse'
        ]);
        
        $shipment = $package->shipment;
        
        $allPackages = $shipment->packages()->orderBy('id')->get();
        
        $currentIndex = 0;
        foreach ($allPackages as $index => $p) {
            if ($p->id === $package->id) {
                $currentIndex = $index + 1;
                break;
            }
        }
        $totalCount = $allPackages->count();
        
        $packageSequence = "$currentIndex من $totalCount";
        
        $destination = $shipment->destinationWarehouse?->name 
            ?? $shipment->batch?->route?->destinationWarehouse?->name 
            ?? 'غير محدد';
        
        $labels = [
            [
                'package' => $package,
                'sequence' => $packageSequence,
            ]
        ];
        
        return view('labels.print', [
            'shipment' => $shipment,
            'labels' => $labels,
            'destination' => $destination,
        ]);
    }

    /**
     * Print labels for all packages in a shipment.
     */
    public function printShipment(Request $request, Shipment $shipment)
    {
        Gate::authorize('view', $shipment);

        $shipment->loadMissing(['packages', 'batch.route.destinationWarehouse']);
        
        $destination = $shipment->destinationWarehouse?->name 
            ?? $shipment->batch?->route?->destinationWarehouse?->name 
            ?? 'غير محدد';
        $packages = $shipment->packages()->orderBy('id')->get();
        $totalCount = $packages->count();
        
        $labels = $packages->map(function ($package, $index) use ($totalCount) {
            return [
                'package' => $package,
                'sequence' => ($index + 1) . " من " . $totalCount,
            ];
        });

        return view('labels.print', [
            'shipment' => $shipment,
            'labels' => $labels,
            'destination' => $destination,
        ]);
    }
}
