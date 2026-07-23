<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-employee page locks (D-026). A closed, per-user deny-list of
            // page keys, stored as JSON for the same reason capabilities are:
            // the list is fixed in code, never user-defined, so a pivot table
            // would invite the permission matrix this deliberately is not.
            $table->json('locked_pages')->nullable()->after('capabilities');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locked_pages');
        });
    }
};
