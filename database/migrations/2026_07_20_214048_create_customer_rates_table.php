<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')->restrictOnDelete();
            $table->foreignId('route_id')
                ->constrained('routes')->restrictOnDelete();

            // Integer cents. Never binary floating point for money.
            $table->unsignedBigInteger('rate_per_kg_cents');

            $table->timestamps();

            // One current rate per customer per route. Effective-dating is
            // deferred past version 1 by D-018: the rate snapshot taken on the
            // shipment at batch assignment already preserves history.
            $table->unique(['customer_id', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_rates');
    }
};
