<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-branch opt-in for each SMS automation. Every flag defaults OFF so
 * existing companies keep their current behaviour (WhatsApp/legacy SMS) until
 * an owner deliberately enables an automation for a branch. Each automation is
 * independent and several can be on at once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_automation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->boolean('confirmation_enabled')->default(false);

            $table->boolean('reminder_enabled')->default(false);
            $table->unsignedSmallInteger('reminder_offset_minutes')->default(60);

            $table->boolean('followup_enabled')->default(false);
            $table->unsignedSmallInteger('followup_days')->default(15);

            $table->timestamps();

            $table->unique(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_automation_settings');
    }
};
