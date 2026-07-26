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
        // Update packages
        DB::table('packages')->where('status', 'received')->update(['status' => 'created']);
        DB::table('packages')->where('status', 'arrived')->update(['status' => 'arrived_destination']);

        // Update package_status_events
        DB::table('package_status_events')->where('status', 'received')->update(['status' => 'created']);
        DB::table('package_status_events')->where('status', 'arrived')->update(['status' => 'arrived_destination']);

        DB::table('package_status_events')->where('previous_status', 'received')->update(['previous_status' => 'created']);
        DB::table('package_status_events')->where('previous_status', 'arrived')->update(['previous_status' => 'arrived_destination']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration
    }
};
