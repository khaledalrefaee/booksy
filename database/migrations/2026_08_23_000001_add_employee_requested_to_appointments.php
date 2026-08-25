<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records whether the customer explicitly picked a specific staff member, as
 * opposed to "any available professional". The resolved employee_id alone can
 * no longer answer this — the group booker always fills it in — so the booked /
 * reminder messages need this flag to know when to name the staff member.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('employee_requested')->default(false)->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('employee_requested');
        });
    }
};
