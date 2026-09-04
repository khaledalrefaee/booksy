<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A record of a customer redeeming points for a reward. Snapshots the reward
 * details so history stays readable even if the reward is later edited/deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_reward_id')->nullable()->constrained('loyalty_rewards')->nullOnDelete();
            $table->string('reward_name');
            $table->unsignedInteger('points_spent');
            $table->string('type', 20);
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('redeemed_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            // issued (voucher created) | used (applied to an invoice) | expired
            $table->string('status', 20)->default('issued');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
    }
};
