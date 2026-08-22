<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_confirmations', function (Blueprint $table) {
            // Customer-supplied cancellation reason (preset label + optional note),
            // captured on the branded cancel page before the appointment is dropped.
            $table->string('reason', 500)->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_confirmations', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
