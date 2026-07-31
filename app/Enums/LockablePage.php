<?php

namespace App\Enums;

/**
 * Every page an administrator may lock for an employee, keyed by the segment
 * that identifies it in a panel route name.
 *
 * This is the single source the lock toggles, the stored locks, and the
 * enforcement middleware all read — one list so the three cannot disagree. The
 * dashboard is intentionally not here: a home screen is not something to lock
 * someone out of, and it already shows an employee no money.
 */
enum LockablePage: string
{
    case Shipments = 'shipments';
    case Batches = 'batches';
    case Customers = 'customers';
    case CustomerRates = 'customer-rates';
    case Payments = 'payments';
    case Routes = 'routes';
    case Warehouses = 'warehouses';
    case Users = 'users';
    case ReceiveIntoBatch = 'receive-into-batch';
    case ScanPackages = 'scan-packages';

    public function label(): string
    {
        return match ($this) {
            self::Shipments => 'الشحنات',
            self::Batches => 'الرحلات',
            self::Customers => 'العملاء',
            self::CustomerRates => 'أسعار العملاء',
            self::Payments => 'المدفوعات',
            self::Routes => 'المسارات',
            self::Warehouses => 'المستودعات',
            self::Users => 'الموظفون',
            self::ReceiveIntoBatch => 'استلام بضاعة',
            self::ScanPackages => 'تسليم بضاعة',
        };
    }

    /** @return array<string, string> value => Arabic label, for the form */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /**
     * The lockable page a panel route belongs to, or null.
     *
     * Null for the dashboard, login, the locked page, and anything not in the
     * registry — the middleware treats null as "not lockable, let it through".
     */
    public static function fromRouteName(?string $routeName): ?self
    {
        if ($routeName === null) {
            return null;
        }

        // filament.admin.resources.{key}.{action} or filament.admin.pages.{key}
        if (! preg_match('/^filament\.admin\.(?:resources|pages)\.([a-z0-9-]+)/', $routeName, $matches)) {
            return null;
        }

        return self::tryFrom($matches[1]);
    }
}
