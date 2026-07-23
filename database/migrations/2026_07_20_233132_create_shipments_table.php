<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            // Two separate identifiers, deliberately.
            //   reference    - short, human, spoken over the phone by staff.
            //   public_token - long and unguessable, used in the public
            //                  tracking URL. AGENTS.md forbids exposing a
            //                  sequential id publicly, so the reference must
            //                  never appear in that link.
            $table->string('reference')->unique();
            $table->string('public_token', 64)->unique();

            // The billing customer. Distinct from the recipient (D-006).
            $table->foreignId('customer_id')
                ->constrained('customers')->restrictOnDelete();

            // Recipient is a historical snapshot: name and phone only.
            // No address is stored — different people may collect the same
            // shipment, and the Beirut delivery team takes the address at
            // handover (D-019).
            $table->string('recipient_name');
            $table->string('recipient_phone');

            $table->string('status');

            // Exact decimal weight, never rounded and never floating point
            // (D-007). Four decimal places is well beyond scale accuracy and
            // leaves room for summing many packages without drift.
            $table->decimal('total_weight_kg', 12, 4)->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
