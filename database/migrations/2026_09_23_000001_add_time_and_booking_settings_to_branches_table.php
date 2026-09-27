<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-branch time & booking settings (Branch Settings page).
 *
 * Every default reproduces what the platform did before these columns
 * existed, so existing branches behave exactly as they did:
 *   - timezone        = the application timezone (Asia/Damascus)
 *   - 15-minute slots = the interval the booking engine hard-coded
 *   - no minimum notice
 *   - 12-hour clock (what the dashboard calendar showed)
 *
 * Business hours are NOT stored here — they stay in branch_working_hours.
 * Customer-facing rules (online booking, same-day, cancel/reschedule and
 * the cancellation deadline) live in booking_policies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // IANA identifier (e.g. Asia/Dubai) — never a fixed "+03:00" offset.
            $table->string('timezone', 64)->default('Asia/Damascus');
            $table->string('time_format', 3)->default('12h');              // 12h | 24h
            $table->unsignedSmallInteger('appointment_interval')->default(15); // minutes between offered start times
            $table->unsignedSmallInteger('min_booking_notice')->default(0);    // minutes before start
            $table->unsignedSmallInteger('max_booking_days')->default(365);    // days ahead (365 = the old "no limit")
            $table->unsignedTinyInteger('first_day_of_week')->default(0);      // 0=Sun … 6=Sat (Carbon dayOfWeek)
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'time_format',
                'appointment_interval',
                'min_booking_notice',
                'max_booking_days',
                'first_day_of_week',
            ]);
        });
    }
};
