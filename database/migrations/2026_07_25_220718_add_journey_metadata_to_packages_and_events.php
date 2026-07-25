<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('is_delayed')->default(false);
            $table->text('delay_reason')->nullable();
            $table->boolean('delay_reason_is_public')->default(false);
            $table->timestamp('delayed_at')->nullable();
        });

        Schema::table('package_status_events', function (Blueprint $table) {
            $table->string('previous_status')->nullable();
            $table->string('event_kind')->default('progress');
            $table->text('private_reason')->nullable();
            $table->text('public_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('package_status_events', function (Blueprint $table) {
            $table->dropColumn([
                'previous_status',
                'event_kind',
                'private_reason',
                'public_reason',
            ]);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'is_delayed',
                'delay_reason',
                'delay_reason_is_public',
                'delayed_at',
            ]);
        });
    }
};
