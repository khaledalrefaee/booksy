<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GlowRez platform settings.
 *
 *  - platform_settings : flexible key/value store ("group.key" → text). The set of
 *    known keys, their types and validation live in config/platform-settings.php,
 *    so adding a setting never needs a migration.
 *  - platform_links    : the official social / custom links. A real table (not a JSON
 *    blob) because each link is an ordered, individually toggled, validated row.
 *
 * No rows are seeded — nothing about GlowRez is assumed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 32)->index();
            $table->string('key', 100)->unique();          // "general.name_ar"
            $table->longText('value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); // owners.id (no FK: audit hint only)
            $table->timestamps();
        });

        Schema::create('platform_links', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 32);                     // instagram | facebook | … | custom
            $table->string('label', 80);
            $table->string('url', 2048);
            $table->string('icon', 40)->nullable();         // only meaningful for kind = custom
            $table->boolean('is_active')->default(true);
            $table->boolean('show_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'show_public', 'sort_order'], 'platform_links_public_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_links');
        Schema::dropIfExists('platform_settings');
    }
};
