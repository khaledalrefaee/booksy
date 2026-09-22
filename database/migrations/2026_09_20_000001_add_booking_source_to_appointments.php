<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Where the booking came from (instagram|facebook|whatsapp|website|
            // reception|other). Null on legacy rows = unknown/direct.
            // See App\Enums\BookingSource.
            $table->string('booking_source', 20)->nullable()->after('notes');
            $table->index(['company_id', 'booking_source'], 'appts_company_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appts_company_source_idx');
            $table->dropColumn('booking_source');
        });
    }
};
