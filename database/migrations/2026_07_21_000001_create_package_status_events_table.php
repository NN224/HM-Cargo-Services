<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')
                ->constrained('packages')->restrictOnDelete();
            $table->string('status');
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('scanned_at');
            $table->string('source');
            $table->text('note')->nullable();

            $table->index(['package_id', 'scanned_at']);
            $table->index(['warehouse_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_status_events');
    }
};
