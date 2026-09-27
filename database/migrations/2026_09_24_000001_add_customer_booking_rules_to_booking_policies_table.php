<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking & Cancellation Policy becomes the single home of the customer-facing
 * booking rules (unified or per branch, like the rest of the policy):
 *
 *   allow_online_booking / allow_same_day_booking
 *   allow_customer_cancel / allow_customer_reschedule
 *   cancellation_deadline_minutes — the one, enforced deadline for both
 *
 * cancellation_deadline_minutes replaces cancellation_window_hours, which was
 * only ever displayed (never enforced) and couldn't express "30 minutes".
 * Every row starts at 0 = "up to the appointment time", so nothing changes for
 * customers until a business sets its own deadline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_policies', function (Blueprint $table) {
            $table->boolean('allow_online_booking')->default(true)->after('branch_id');
            $table->boolean('allow_same_day_booking')->default(true)->after('allow_online_booking');
            $table->boolean('allow_customer_cancel')->default(true)->after('allow_same_day_booking');
            $table->boolean('allow_customer_reschedule')->default(true)->after('allow_customer_cancel');
            $table->unsignedSmallInteger('cancellation_deadline_minutes')->default(0)->after('allow_customer_reschedule');
        });

        Schema::table('booking_policies', function (Blueprint $table) {
            $table->dropColumn('cancellation_window_hours');
        });
    }

    public function down(): void
    {
        Schema::table('booking_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancellation_window_hours')->default(24)->after('branch_id');
        });

        Schema::table('booking_policies', function (Blueprint $table) {
            $table->dropColumn([
                'allow_online_booking',
                'allow_same_day_booking',
                'allow_customer_cancel',
                'allow_customer_reschedule',
                'cancellation_deadline_minutes',
            ]);
        });
    }
};
