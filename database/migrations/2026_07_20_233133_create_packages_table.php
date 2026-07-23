<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained('shipments')->cascadeOnDelete();

            // System-generated and unique (D-011). Never derived from a
            // timestamp or a small random range: the previous prototype used
            // PKG-{millis}-{rand(1000)}, which collides once two packages are
            // created in the same millisecond.
            $table->string('barcode')->unique();

            // Optional reference to the supplier's own barcode, so a package
            // that already carries one can be scanned rather than retyped.
            // Not unique: two suppliers may reuse a code, and it carries no
            // meaning inside this system.
            $table->string('source_barcode')->nullable();

            $table->string('description')->nullable();

            // Exact decimal, same scale as the shipment total.
            $table->decimal('weight_kg', 12, 4);

            // Package status is its own state machine, separate from the
            // shipment's (AGENTS.md).
            $table->string('status');

            $table->timestamps();

            $table->index(['shipment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
