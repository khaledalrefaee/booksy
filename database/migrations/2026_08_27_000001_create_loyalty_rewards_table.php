<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rewards a branch offers in exchange for loyalty points. Optional — a branch
 * with no rewards simply lets points accumulate with nothing to redeem yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // free_service | percent_all | percent_service
            $table->string('type', 20)->default('free_service');
            // Target service for free_service / percent_service.
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            // Discount amount for percent_all / percent_service.
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->unsignedInteger('points_cost');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
