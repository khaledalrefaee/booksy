<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-message tracking: who, which appointment, type, provider result, and
 * credits charged. `dedupe_key` is the duplicate guard — one logical message
 * (e.g. "confirmation for appointment 42") maps to exactly one row, so a job
 * retried or fired twice cannot double-send or double-charge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            // Which wallet was charged (branch or company pool).
            $table->foreignId('wallet_id')->nullable()->constrained('sms_wallets')->nullOnDelete();
            // confirmation | reminder | followup | manual
            $table->string('message_type', 24);
            $table->string('phone', 32);
            $table->text('body');
            $table->unsignedSmallInteger('segments')->default(1);
            $table->unsignedSmallInteger('credits_used')->default(0);
            // queued | sent | failed | skipped
            $table->string('status', 16)->default('queued');
            $table->string('provider', 24)->default('rasel');
            $table->string('provider_message_id')->nullable();
            $table->string('failure_reason', 512)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['appointment_id', 'message_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
