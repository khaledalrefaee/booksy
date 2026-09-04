<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An add-on (service_type = addon) attaches to one or more parent services.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('addon_service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['parent_service_id', 'addon_service_id'], 'service_addon_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_addon');
    }
};
