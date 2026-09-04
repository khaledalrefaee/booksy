<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One SMS balance per (company, branch). branch_id NULL = the shared company
 * pool a branch falls back to when it has no dedicated allocation. `balance`
 * is a cached running total kept in sync with the credit ledger; the ledger
 * (sms_transactions) is the source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Null = company-level pool (fallback for its branches).
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('balance')->default(0);
            $table->unsignedInteger('total_purchased')->default(0);
            $table->unsignedInteger('total_used')->default(0);
            $table->unsignedInteger('low_balance_threshold')->default(50);
            $table->boolean('notify_low_balance')->default(true);
            // Throttle timestamps so we alert once per crossing, not every send.
            $table->timestamp('notified_low_at')->nullable();
            $table->timestamp('notified_zero_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_wallets');
    }
};
