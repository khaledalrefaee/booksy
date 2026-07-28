<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            // Financial rows are never hard-deleted — they get voided with a reason.
            $table->timestamp('voided_at')->nullable()->after('notes');
            $table->string('void_reason', 500)->nullable()->after('voided_at');
            $table->foreignId('voided_by')->nullable()->after('void_reason')
                ->constrained('owners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
