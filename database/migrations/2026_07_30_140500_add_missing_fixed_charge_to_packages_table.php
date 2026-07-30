<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('packages', 'fixed_charge_cents')) {
            return;
        }

        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('fixed_charge_cents')
                ->nullable()
                ->after('custom_rate_per_kg_cents');
        });
    }

    public function down(): void
    {
        // This corrective migration also runs on databases where the column
        // came from the original package-pricing migration. Dropping it here
        // would destroy valid pricing data on those databases.
    }
};
