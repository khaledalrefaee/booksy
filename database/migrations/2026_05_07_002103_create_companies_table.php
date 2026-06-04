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
        Schema::disableForeignKeyConstraints();

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('logo')->nullable();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('password');
            $table->rememberToken();
            $table->string('status', 32)->default('pending');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
