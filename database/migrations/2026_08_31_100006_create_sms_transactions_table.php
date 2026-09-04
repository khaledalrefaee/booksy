<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Credit Ledger — an append-only record of every credit movement so the
 * owner can see exactly how each SMS was added and consumed. `credits` is
 * signed (+grant/+purchase/+refund, −consume/−expire); `balance_after` is the
 * wallet balance immediately after this row, giving an auditable running total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('sms_wallets')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('sms_credit_batches')->nullOnDelete();
            $table->foreignId('sms_message_id')->nullable()->constrained('sms_messages')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('sms_packages')->nullOnDelete();
            // grant | purchase | consume | refund | expire | adjustment
            $table->string('type', 16);
            $table->integer('credits');
            $table->integer('balance_after');
            // owner | system
            $table->string('created_by', 16)->default('system');
            $table->foreignId('created_by_owner_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_transactions');
    }
};
