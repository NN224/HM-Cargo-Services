<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('origin_airport_name')->nullable();
            $table->string('destination_airport_name')->nullable();
            $table->string('delivery_office_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn([
                'origin_airport_name',
                'destination_airport_name',
                'delivery_office_name',
            ]);
        });
    }
};
