<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer's loyalty point balance at a specific branch. Points are earned and
 * redeemed per branch; `customers.loyalty_points` keeps the grand total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_loyalty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty');
    }
};
