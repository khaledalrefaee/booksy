<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_drawer_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_drawer_session_id')->constrained()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->decimal('opening_amount', 14, 2)->default(0);
            $table->decimal('closing_amount', 14, 2)->nullable();
            $table->decimal('expected_amount', 14, 2)->nullable();
            $table->decimal('variance', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['cash_drawer_session_id', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_drawer_balances');
    }
};
