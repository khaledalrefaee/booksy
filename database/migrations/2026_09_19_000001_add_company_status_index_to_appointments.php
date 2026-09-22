<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboards and appointment lists filter by (company_id, status) constantly —
 * pending counts, status breakdowns, "cancelled" buckets. The existing
 * (company_id, start_time) index doesn't help those, so status aggregates had
 * to scan every one of a company's appointments. This composite covers them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'appts_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appts_company_status_idx');
        });
    }
};
