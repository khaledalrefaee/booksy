<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('SYP');
            $table->unsignedInteger('duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->timestamp('discount_starts_at')->nullable();
            $table->timestamp('discount_ends_at')->nullable();

            // Service classification: standard | package | membership | addon | consultation
            $table->string('service_type', 20)->default('standard');
            // Pricing model: fixed | from | range. `price` is the base/"from" value,
            // `price_to` is the upper bound of a range.
            $table->string('price_type', 10)->default('fixed');
            $table->decimal('price_to', 10, 2)->nullable();
            // Merchandising / visibility
            $table->string('image_path')->nullable();
            $table->boolean('is_bookable_online')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_recommended')->default(false);
            // Manual ordering within a branch (drag-and-drop)
            $table->unsignedInteger('sort_order')->default(0);
            // Membership-specific (nullable; only used when service_type = membership)
            $table->unsignedInteger('membership_validity_days')->nullable();
            $table->unsignedInteger('membership_sessions')->nullable();
            // Optional merchandising badges: ["most_requested","new","special_offer","premium"]
            $table->json('badges')->nullable();
            // Consultation semantics (only meaningful when service_type = consultation)
            $table->boolean('is_free')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
