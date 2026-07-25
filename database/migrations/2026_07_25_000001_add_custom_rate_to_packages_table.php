<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('custom_rate_per_kg_cents')->nullable()->after('weight_kg');
            $table->unsignedInteger('fixed_charge_cents')->nullable()->after('custom_rate_per_kg_cents');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['custom_rate_per_kg_cents', 'fixed_charge_cents']);
        });
    }
};
