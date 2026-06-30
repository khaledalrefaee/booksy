<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24); // purchase, sale, transfer_in, transfer_out, adjustment, return
            $table->integer('quantity'); // positive = in, negative = out
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('reference')->nullable(); // invoice #, transfer #, etc.
            $table->foreignId('related_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
