<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Nullable to ensure we don't break existing legacy shipments
            // that were created before this column existed.
            $table->foreignId('destination_warehouse_id')
                ->nullable()
                ->after('recipient_phone')
                ->constrained('warehouses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_warehouse_id');
        });
    }
};
