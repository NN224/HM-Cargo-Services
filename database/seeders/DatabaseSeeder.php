<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
        Route::firstOrCreate(
            ['name' => 'دبي → لبنان'],
            [
                'origin_warehouse_id'      => $dubai->id,
                'destination_warehouse_id' => $beirut->id,
                'transit_warehouse_id'     => null,
                'is_active'                => true,
            ],
        );

        Route::firstOrCreate(
            ['name' => 'دبي → سوريا (مباشر)'],
            [
                'origin_warehouse_id'      => $dubai->id,
                'destination_warehouse_id' => $damascus->id,
                'transit_warehouse_id'     => null,
                'is_active'                => true,
            ],
        );

        Route::firstOrCreate(
            ['name' => 'دبي → بيروت → سوريا'],
            [
                'origin_warehouse_id'      => $dubai->id,
                'destination_warehouse_id' => $damascus->id,
                'transit_warehouse_id'     => $beirut->id,
                'is_active'                => true,
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
                'name'         => 'مدير النظام',
                'password'     => $password,
                'role'         => UserRole::Administrator,
                'warehouse_id' => $dubai->id,
                'is_active'    => true,
            ],
        );

        $this->command?->info('Administrator and initial routes seeded. Change the password after first login.');
    }
}
