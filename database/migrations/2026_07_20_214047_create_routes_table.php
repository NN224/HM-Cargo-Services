<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            $table->foreignId('origin_warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();

            // Dubai->Beirut->Syria is a different route from Dubai->Syria
            // direct and may carry a different customer price (D-007).
            $table->foreignId('transit_warehouse_id')
                ->nullable()->constrained('warehouses')->restrictOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // NOTE: the "origin must differ from destination" invariant is enforced
        // in App\Models\Route::booted() rather than as a database CHECK.
        // Laravel's Blueprint has no portable check() helper, and SQLite cannot
        // add a CHECK constraint after table creation. Emitting SQLite-only
        // trigger SQL here would break the PostgreSQL parity that D-002
        // requires for production, so the constraint is added to PostgreSQL in
        // Phase 7 instead.
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
