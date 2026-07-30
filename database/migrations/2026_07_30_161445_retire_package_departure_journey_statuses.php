<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('packages')
            ->where('status', 'in_transit')
            ->update(['status' => 'arrived_origin_airport']);

        DB::table('packages')
            ->where('status', 'departed_transit')
            ->update(['status' => 'arrived_transit']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally irreversible: the retired states cannot be reconstructed
        // without inventing which packages had actually departed.
    }
};
