<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Display-only catalog (price + image); branch_id null = visible for whole company.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('image')->nullable();
            $table->string('sku', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_display_only')->default(true);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
