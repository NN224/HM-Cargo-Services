<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            // A batch travels exactly one route, and that route is what makes
            // its shipments billable (D-004).
            $table->foreignId('route_id')
                ->constrained('routes')->restrictOnDelete();

            $table->string('status');

            // The company's own cost for the whole route, in integer cents,
            // snapshotted when the batch is dispatched. One figure covers the
            // entire route even when it passes through a transit warehouse.
            // Batch cost keeps its cents — the customer rounding rule must
            // never be applied here (D-008, D-020).
            $table->unsignedBigInteger('cost_per_kg_cents')->nullable();

            $table->date('dispatched_on')->nullable();
            $table->date('arrived_on')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
