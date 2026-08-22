<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // null branch_id = the company-wide default policy
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();

            // ── Cancellation window ──
            $table->unsignedSmallInteger('cancellation_window_hours')->default(24); // 0 = self-cancel disabled

            // ── Lateness & attendance ──
            $table->unsignedSmallInteger('late_grace_minutes')->default(15);
            $table->enum('late_action', ['staff_decides', 'auto_cancel'])->default('staff_decides');

            // ── Reminders ──
            $table->enum('reminder_channel', ['whatsapp', 'sms'])->default('whatsapp');
            $table->boolean('reminder_on_booking')->default(true);
            $table->boolean('reminder_24h')->default(true);
            $table->boolean('reminder_3h')->default(true);
            $table->boolean('require_confirmation')->default(true);
            $table->unsignedSmallInteger('confirmation_deadline_hours')->default(6); // 0 = off

            // ── No-show protection (reliability gate) ──
            $table->boolean('protection_enabled')->default(true);
            $table->unsignedTinyInteger('offense_threshold')->default(2);
            $table->unsignedSmallInteger('offense_window_days')->default(60);
            $table->boolean('action_alert_staff')->default(true);
            $table->boolean('action_manual_confirm')->default(true);

            // ── Optional deposit (off by default — cash market) ──
            $table->boolean('deposit_enabled')->default(false);
            $table->enum('deposit_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->enum('deposit_scope', ['at_risk', 'new', 'all'])->default('at_risk');

            // ── Message templates (null = fall back to built-in default) ──
            $table->text('msg_confirm')->nullable();
            $table->text('msg_reminder_24h')->nullable();
            $table->text('msg_reminder_3h')->nullable();
            $table->text('msg_unconfirmed')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'branch_id']);
        });

        Schema::table('companies', function (Blueprint $table) {
            // 'unified' = one policy for every branch; 'per_branch' = each branch overrides
            $table->enum('booking_policy_mode', ['unified', 'per_branch'])
                ->default('unified')
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('booking_policy_mode');
        });
        Schema::dropIfExists('booking_policies');
    }
};
