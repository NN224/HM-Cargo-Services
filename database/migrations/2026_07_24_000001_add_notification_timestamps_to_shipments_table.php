<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('intake_notified_at')->nullable()->after('public_token');
            $table->timestamp('arrival_notified_at')->nullable()->after('intake_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['intake_notified_at', 'arrival_notified_at']);
        });
    }
};
