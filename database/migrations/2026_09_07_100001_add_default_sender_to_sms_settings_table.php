<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the platform's chosen default Rasel SMS sender (from
 * GET /api/v2/sms-senders). One platform account = one owner-level choice, so
 * it lives on the singleton sms_settings row, not per company. Only the id is
 * sent to Rasel (sender.id); the name is cached for display.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_settings', function (Blueprint $table) {
            $table->string('default_sender_id')->nullable()->after('currency');
            $table->string('default_sender_name')->nullable()->after('default_sender_id');
        });
    }

    public function down(): void
    {
        Schema::table('sms_settings', function (Blueprint $table) {
            $table->dropColumn(['default_sender_id', 'default_sender_name']);
        });
    }
};
