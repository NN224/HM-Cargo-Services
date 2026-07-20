<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // A shipment may belong to only one active batch at a time.
            $table->foreignId('batch_id')
                ->nullable()->after('customer_id')
                ->constrained('batches')->nullOnDelete();

            // The customer's rate for the batch's route, captured at the
            // moment of assignment. Later rate changes must never rewrite a
            // historical charge, so this is never recomputed.
            $table->unsignedBigInteger('rate_per_kg_cents')->nullable();

            // Exact weight times the snapshotted rate, kept to the cent and
            // never overwritten. This is the figure the final charge is
            // explained against.
            $table->unsignedBigInteger('computed_charge_cents')->nullable();

            // What the operator decided the customer pays (D-020). Typed as
            // the final amount, not as an adjustment. The rounding difference
            // is derived from these two, never stored, so they cannot drift.
            $table->unsignedBigInteger('final_charge_cents')->nullable();

            $table->unsignedBigInteger('paid_amount_cents')->default(0);

            $table->timestamp('priced_at')->nullable();

            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropColumn([
                'rate_per_kg_cents', 'computed_charge_cents',
                'final_charge_cents', 'paid_amount_cents', 'priced_at',
            ]);
        });
    }
};
