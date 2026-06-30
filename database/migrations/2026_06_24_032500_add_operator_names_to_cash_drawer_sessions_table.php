<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_drawer_sessions', function (Blueprint $table) {
            $table->string('opened_by_name', 100)->nullable()->after('opened_by');
            $table->string('closed_by_name', 100)->nullable()->after('closed_by');
            $table->string('reconciled_by_name', 100)->nullable()->after('reconciled_by');
            $table->string('voided_by_name', 100)->nullable()->after('voided_by');
        });
    }

    public function down(): void
    {
        Schema::table('cash_drawer_sessions', function (Blueprint $table) {
            $table->dropColumn(['opened_by_name', 'closed_by_name', 'reconciled_by_name', 'voided_by_name']);
        });
    }
};
