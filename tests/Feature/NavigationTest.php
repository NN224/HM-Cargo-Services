<?php

use App\Filament\Pages\ReceiveIntoBatch;
use App\Filament\Pages\ScanPackages;
use App\Filament\Resources\Batches\BatchResource;
use App\Filament\Resources\CustomerRates\CustomerRateResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Routes\RouteResource;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Warehouses\WarehouseResource;

/**
 * The shape of the sidebar.
 *
 * Two items once shared a sort value, which leaves their order to whatever
 * Filament falls back on — the menu quietly rearranged itself and nobody could
 * say why. Four others shared one icon, so the list read as a wall of
 * identical boxes. Neither is the kind of thing a feature test would catch,
 * and both are what an operator actually looks at all day.
 */
function navigationItems(): array
{
    return [
        'استلام بضاعة' => ReceiveIntoBatch::class,
        'مسح الطرود' => ScanPackages::class,
        'الشحنات' => ShipmentResource::class,
        'الرحلات' => BatchResource::class,
        'العملاء' => CustomerResource::class,
        'أسعار العملاء' => CustomerRateResource::class,
        'المدفوعات' => PaymentResource::class,
        'المسارات' => RouteResource::class,
        'المستودعات' => WarehouseResource::class,
        'الموظفون' => UserResource::class,
    ];
}

/** Reads a protected static property off a Filament class. */
function navProperty(string $class, string $property): mixed
{
    return (new ReflectionClass($class))->getStaticPropertyValue($property);
}

test('every navigation item declares a group', function () {
    foreach (navigationItems() as $label => $class) {
        expect(navProperty($class, 'navigationGroup'))
            ->not->toBeNull("«{$label}» بلا مجموعة");
    }
});

test('every navigation item declares a sort position', function () {
    // An item with no sort lands wherever Filament happens to put it, which is
    // how the new intake screen first appeared in the middle of the settings.
    foreach (navigationItems() as $label => $class) {
        expect(navProperty($class, 'navigationSort'))
            ->not->toBeNull("«{$label}» بلا ترتيب");
    }
});

test('no two items in the same group share a sort position', function () {
    $seen = [];

    foreach (navigationItems() as $label => $class) {
        $key = navProperty($class, 'navigationGroup').'#'.navProperty($class, 'navigationSort');

        expect($seen)->not->toHaveKey(
            $key,
            "«{$label}» يتصادم مع «".($seen[$key] ?? '').'» على نفس الترتيب'
        );

        $seen[$key] = $label;
    }
});

test('no two navigation items share an icon', function () {
    $seen = [];

    foreach (navigationItems() as $label => $class) {
        $icon = navProperty($class, 'navigationIcon');
        $value = $icon instanceof BackedEnum ? $icon->value : (string) $icon;

        expect($seen)->not->toHaveKey(
            $value,
            "«{$label}» يحمل نفس أيقونة «".($seen[$value] ?? '').'»'
        );

        $seen[$value] = $label;
    }
});

test('the daily operations group sorts before customers and settings', function () {
    // What an operator touches every day sits at the top; what an
    // administrator opens once a month sits at the bottom.
    expect(navProperty(ReceiveIntoBatch::class, 'navigationGroup'))->toBe('العمليات')
        ->and(navProperty(ScanPackages::class, 'navigationGroup'))->toBe('العمليات')
        ->and(navProperty(ShipmentResource::class, 'navigationGroup'))->toBe('العمليات')
        ->and(navProperty(BatchResource::class, 'navigationGroup'))->toBe('العمليات')
        ->and(navProperty(RouteResource::class, 'navigationGroup'))->toBe('الإعدادات')
        ->and(navProperty(WarehouseResource::class, 'navigationGroup'))->toBe('الإعدادات')
        ->and(navProperty(UserResource::class, 'navigationGroup'))->toBe('الإعدادات');
});
