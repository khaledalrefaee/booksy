<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_head_office')->default(false);
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->string('booking_mode', 20)->default('marketplace');
            $table->string('slug', 255)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->json('phones')->nullable();
            $table->text('address')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->unsignedSmallInteger('country_id')->nullable();
            $table->unsignedSmallInteger('governorate_id')->nullable();
            $table->unsignedInteger('area_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('qr_code')->nullable();
            $table->string('landline_phone')->nullable();
            $table->json('landlines')->nullable();
            $table->string('overpayment_to')->nullable();
            $table->timestamps();

            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->foreign('governorate_id')->references('id')->on('governorates')->nullOnDelete();
            $table->foreign('area_id')->references('id')->on('areas')->nullOnDelete();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
