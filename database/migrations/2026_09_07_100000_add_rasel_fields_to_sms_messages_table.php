<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the delivery outcome fields returned by Rasel's
 * POST /api/v2/messages/send response, so History/Logs can show what actually
 * happened at the provider. These are provider-side facts ONLY — the message's
 * cost to the COMPANY is still the GlowRez `credits_used`; `estimated_cost` is
 * Rasel's external USD estimate for reference, never a company balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            // requestId — Rasel's per-request id (support/debug correlation).
            $table->string('request_id')->nullable()->after('provider_message_id');
            // tracking.usageId — Rasel usage record id.
            $table->string('usage_id')->nullable()->after('request_id');
            // tracking.queueId — set when the send was queued (202).
            $table->string('queue_id')->nullable()->after('usage_id');
            // resolved.provider — the real downstream carrier (e.g. sms_mtn).
            $table->string('resolved_provider', 40)->nullable()->after('queue_id');
            // resolved.senderSource — account_default | platform | session ...
            $table->string('sender_source', 40)->nullable()->after('resolved_provider');
            // Rasel body.status — sent | queued | scheduled.
            $table->string('provider_status', 24)->nullable()->after('sender_source');
            // billing.estimatedCost / billing.currency — Rasel's external cost.
            $table->decimal('estimated_cost', 12, 4)->nullable()->after('provider_status');
            $table->string('cost_currency', 8)->nullable()->after('estimated_cost');
            // Error `code` on a failed/blocked send (VALIDATION_ERROR, etc.).
            $table->string('error_code', 64)->nullable()->after('failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropColumn([
                'request_id', 'usage_id', 'queue_id', 'resolved_provider',
                'sender_source', 'provider_status', 'estimated_cost',
                'cost_currency', 'error_code',
            ]);
        });
    }
};
