<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Round 2 of the services workbench: a flexible badge system, consultation
 * semantics (free / needs approval), and a proper add-on ↔ parent link.
 * All additive; the old is_popular/is_recommended booleans are kept and simply
 * backfilled into the new `badges` array so nothing is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Optional merchandising badges: ["most_requested","new","special_offer","premium"]
            $table->json('badges')->nullable()->after('is_recommended');
            // Consultation semantics (only meaningful when service_type = consultation)
            $table->boolean('is_free')->default(false)->after('badges');
            $table->boolean('requires_approval')->default(false)->after('is_free');
        });

        // An add-on (service_type = addon) attaches to one or more parent services.
        Schema::create('service_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('addon_service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['parent_service_id', 'addon_service_id'], 'service_addon_unique');
        });

        // Backfill: an old "popular" service becomes the "most requested" badge.
        foreach (DB::table('services')->where('is_popular', true)->pluck('id') as $id) {
            DB::table('services')->where('id', $id)->update(['badges' => json_encode(['most_requested'])]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_addon');
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['badges', 'is_free', 'requires_approval']);
        });
    }
};
