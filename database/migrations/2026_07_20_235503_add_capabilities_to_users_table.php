<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // A fixed, additive set of grants on top of the warehouse employee
            // role (D-022). Stored as JSON rather than a pivot table because
            // the list is closed and never user-defined — a permissions table
            // would invite exactly the matrix this decision rejects.
            $table->json('capabilities')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('capabilities');
        });
    }
};
