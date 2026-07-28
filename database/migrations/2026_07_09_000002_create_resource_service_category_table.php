<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A resource linked to a category is required by every service of that
        // category in the resource's own branch (unless overridden per service).
        Schema::create('resource_service_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->unique(['resource_id', 'service_category_id'], 'resource_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_service_category');
    }
};
