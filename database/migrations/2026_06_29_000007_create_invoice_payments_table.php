<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 24)->default('cash');
            $table->string('notes')->nullable();
            $table->string('recorded_by_name')->nullable();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount_paid', 12, 2)->default(0)->after('total');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
