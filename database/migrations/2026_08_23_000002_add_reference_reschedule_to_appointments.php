<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking reference (support + human-readable id, shared across a grouped visit),
 * an idempotency key that makes the create endpoint safe against double-submits,
 * and the audit fields a reschedule needs. All nullable / defaulted so existing
 * rows are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('reference', 20)->nullable()->after('booking_group_id')->index();
            $table->string('idempotency_key', 64)->nullable()->after('reference')->index();
            $table->dateTime('original_start_time')->nullable()->after('start_time');
            $table->unsignedTinyInteger('reschedule_count')->default(0)->after('original_start_time');
            $table->dateTime('rescheduled_at')->nullable()->after('reschedule_count');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['reference', 'idempotency_key', 'original_start_time', 'reschedule_count', 'rescheduled_at']);
        });
    }
};
