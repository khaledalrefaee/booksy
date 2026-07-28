<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive-only upgrade of the services table to support the redesigned
 * services workbench: multiple service types, flexible pricing (fixed / from /
 * range), online-booking visibility, merchandising badges, an image, and a
 * manual sort order for drag-and-drop. All columns are nullable or defaulted so
 * existing rows keep working untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Service classification: standard | package | membership | addon | consultation
            $table->string('service_type', 20)->default('standard')->after('service_category_id');

            // Pricing model: fixed | from | range. `price` is the base/"from" value,
            // `price_to` is the upper bound of a range.
            $table->string('price_type', 10)->default('fixed')->after('price');
            $table->decimal('price_to', 10, 2)->nullable()->after('price_type');

            // Merchandising / visibility
            $table->string('image_path')->nullable()->after('duration_minutes');
            $table->boolean('is_bookable_online')->default(true)->after('is_active');
            $table->boolean('is_popular')->default(false)->after('is_bookable_online');
            $table->boolean('is_recommended')->default(false)->after('is_popular');

            // Manual ordering within a branch (drag-and-drop)
            $table->unsignedInteger('sort_order')->default(0)->after('is_recommended');

            // Membership-specific (nullable; only used when service_type = membership)
            $table->unsignedInteger('membership_validity_days')->nullable()->after('sort_order');
            $table->unsignedInteger('membership_sessions')->nullable()->after('membership_validity_days');
        });

        // Seed a stable initial order so drag-and-drop has something to reorder:
        // keep the existing alphabetical feel by ordering per branch on id.
        $rows = DB::table('services')->orderBy('branch_id')->orderBy('name_en')->orderBy('id')->get(['id', 'branch_id']);
        $order = [];
        foreach ($rows as $row) {
            $order[$row->branch_id] = ($order[$row->branch_id] ?? 0) + 1;
            DB::table('services')->where('id', $row->id)->update(['sort_order' => $order[$row->branch_id]]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'service_type',
                'price_type',
                'price_to',
                'image_path',
                'is_bookable_online',
                'is_popular',
                'is_recommended',
                'sort_order',
                'membership_validity_days',
                'membership_sessions',
            ]);
        });
    }
};
