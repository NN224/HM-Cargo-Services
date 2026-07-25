<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the initial administrator, the warehouses, and the three routes
     * approved in D-005.
     *
     * The three routes are seeded so the system is immediately operational
     * after a fresh install: a user can create a batch and receive cargo
     * without first having to configure warehouses and routes manually.
     *
     * The password is read from ADMIN_PASSWORD so no credential is ever
     * committed. Seeding is skipped, loudly, when it is unset.
     */
    public function run(): void
    {
        $dubai = Warehouse::firstOrCreate(
            ['name' => 'Dubai'],
            ['location' => 'دبي، الإمارات العربية المتحدة'],
        );

        $beirut = Warehouse::firstOrCreate(
            ['name' => 'Beirut'],
            ['location' => 'بيروت، لبنان'],
        );

        $damascus = Warehouse::firstOrCreate(
            ['name' => 'Damascus'],
            ['location' => 'دمشق، سوريا'],
        );

        // The three initial routes approved in D-005.
        // Seeded so the system is operational immediately — no manual route
        // setup is required before a user can create a batch and receive cargo.
        Route::updateOrCreate(
            ['name' => 'Dubai → Lebanon'],
            [
                'origin_warehouse_id' => $dubai->id,
                'destination_warehouse_id' => $beirut->id,
                'transit_warehouse_id' => null,
                'origin_airport_name' => 'مطار دبي',
                'destination_airport_name' => 'مطار بيروت',
                'delivery_office_name' => 'مكتب بيروت',
                'is_active' => true,
            ],
        );

        Route::updateOrCreate(
            ['name' => 'Dubai → Syria (Direct)'],
            [
                'origin_warehouse_id' => $dubai->id,
                'destination_warehouse_id' => $damascus->id,
                'transit_warehouse_id' => null,
                'origin_airport_name' => 'مطار دبي',
                'destination_airport_name' => 'مطار دمشق',
                'delivery_office_name' => 'مكتب دمشق',
                'is_active' => true,
            ],
        );

        Route::updateOrCreate(
            ['name' => 'Dubai → Beirut → Syria'],
            [
                'origin_warehouse_id' => $dubai->id,
                'destination_warehouse_id' => $damascus->id,
                'transit_warehouse_id' => $beirut->id,
                'origin_airport_name' => 'مطار دبي',
                'destination_airport_name' => 'مطار بيروت',
                'delivery_office_name' => 'مكتب دمشق',
                'is_active' => true,
            ],
        );

        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            $this->command?->warn(
                'ADMIN_PASSWORD is not set — administrator was NOT created. '
                .'Set ADMIN_PASSWORD in .env then run: php artisan db:seed'
            );

            return;
        }

        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@hmcargo.ae')],
            [
                'name' => 'مدير النظام',
                'password' => $password,
                'role' => UserRole::Administrator,
                'warehouse_id' => $dubai->id,
                'is_active' => true,
            ],
        );

        // Initial Real Customers List with Normalized Country Codes (+961 / +963 / +971)
        $customers = [
            ['name' => 'ahmad m', 'phone' => '+961 76 821 824'],
            ['name' => 'AMANI EL ASHI', 'phone' => '+961 70 660 048'],
            ['name' => 'BILAL KATRANJE', 'phone' => '+961 71 445 221'],
            ['name' => 'CARINE AWAD', 'phone' => '+961 76 386 975'],
            ['name' => 'DIANA HASSAN', 'phone' => '+961 70 699 075'],
            ['name' => 'ELINA CHAKOUR', 'phone' => '+961 81 707 943'],
            ['name' => 'FATIMA FARASHA', 'phone' => '+961 71 598 429'],
            ['name' => 'GHYDAA EL NADAF', 'phone' => '+961 81 318 424'],
            ['name' => 'HASAN ABOLHASAN', 'phone' => '+961 76 988 874'],
            ['name' => 'JANA MATTAR', 'phone' => '+961 70 350 901'],
            ['name' => 'JESSY ISSA', 'phone' => '+961 71 537 456'],
            ['name' => 'LAILA BAGHDADI', 'phone' => '+963 940 881 483'],
            ['name' => 'LINA SALMAN', 'phone' => '+961 76 823 868'],
            ['name' => 'MAGUY HAFEZ', 'phone' => '+961 71 056 265'],
            ['name' => 'MARIANNE BAAKLINI', 'phone' => '+961 71 064 967'],
            ['name' => 'MARIANNE SALIBA', 'phone' => '+961 81 568 611'],
            ['name' => 'MAROUN MERHJ', 'phone' => '+961 3 462 265'],
            ['name' => 'MARYAM MEHYDINE', 'phone' => '+961 78 837 061'],
            ['name' => 'MONZER AWADA', 'phone' => '+961 70 489 088'],
            ['name' => 'nabil', 'phone' => '+971 54 366 5548'],
            ['name' => 'NABILLA AL ARAB', 'phone' => '+961 3 595 116'],
            ['name' => 'NOUR AL HAJJ', 'phone' => '+961 81 808 137'],
            ['name' => 'NOUR EL HAJJ', 'phone' => '+961 81 808 137'],
            ['name' => 'RENEE EL CHEIKH', 'phone' => '+961 71 067 084'],
            ['name' => 'RIM ABOU HAMDAN', 'phone' => '+961 70 275 050'],
            ['name' => 'RITA NASRALLAH', 'phone' => '+961 76 386 975'],
            ['name' => 'SAMIRA EL NAHHAS', 'phone' => '+961 70 041 071'],
            ['name' => 'SHIPSHARKS', 'phone' => '+961 81 175 526'],
            ['name' => 'VANESSA MELHEM', 'phone' => '+961 76 564 848'],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['phone' => $c['phone']],
                ['name' => $c['name']]
            );
        }

        $this->command?->info('Administrator, initial routes, and customer list seeded successfully.');
    }
}
