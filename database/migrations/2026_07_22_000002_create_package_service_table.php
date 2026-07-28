<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a package service to the child services it bundles. A package is just a
 * row in `services` with service_type = 'package'; this pivot records which
 * services it includes, whether each is optional, and their display order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('child_service_id')->constrained('services')->cascadeOnDelete();
            $table->boolean('is_optional')->default(false);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['package_id', 'child_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_service');
    }
};
