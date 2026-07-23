<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the initial administrator and the warehouses behind the three
     * routes approved in D-005.
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

        Warehouse::firstOrCreate(
            ['name' => 'Beirut'],
            ['location' => 'بيروت، لبنان'],
        );

        Warehouse::firstOrCreate(
            ['name' => 'Damascus'],
            ['location' => 'دمشق، سوريا'],
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

        $this->command?->info('Administrator seeded. Change the password after first login.');
    }
}
