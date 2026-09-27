<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a new online booking is confirmed straight away or waits for the
 * business to approve it. Defaults to false = "needs approval", which is how
 * every online booking has been created so far (status pending).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_policies', function (Blueprint $table) {
            $table->boolean('auto_confirm_online_bookings')->default(false)->after('allow_same_day_booking');
        });
    }

    public function down(): void
    {
        Schema::table('booking_policies', function (Blueprint $table) {
            $table->dropColumn('auto_confirm_online_bookings');
        });
    }
};
