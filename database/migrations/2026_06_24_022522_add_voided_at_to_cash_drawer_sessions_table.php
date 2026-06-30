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
            $table->timestamp('voided_at')->nullable()->after('reconciled_at');
            $table->foreignId('voided_by')->nullable()->after('voided_at')
                  ->constrained('employees')->nullOnDelete();
            $table->string('void_reason', 255)->nullable()->after('voided_by');
        });
    }

    public function down(): void
    {
        Schema::table('cash_drawer_sessions', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason']);
        });
    }
};
