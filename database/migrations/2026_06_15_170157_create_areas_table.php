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
        Schema::create('areas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('governorate_id');
            $table->string('name_en', 100);
            $table->string('name_ar', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreign('governorate_id')->references('id')->on('governorates')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
