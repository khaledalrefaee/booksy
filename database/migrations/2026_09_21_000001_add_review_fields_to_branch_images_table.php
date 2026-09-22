<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing branch_images table with the review workflow, source
 * tracking, cover flag and technical-quality metadata. Existing rows are
 * back-filled to `approved` / `business` and the first photo of every branch
 * becomes its cover, so nothing that is live today disappears or changes order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_images', function (Blueprint $table) {
            // Review workflow. Default `approved` so any legacy insert path stays
            // visible; upload controllers always set the real status explicitly.
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('approved')->after('type');

            // Who provided the photo. Business photos go through review; team
            // photos are trusted and approved on upload.
            $table->enum('source', ['business', 'glowrez_team'])
                ->default('business')->after('status');

            // Exactly one cover per branch (enforced in the application layer).
            $table->boolean('is_cover')->default(false)->after('source');

            // Review bookkeeping.
            $table->string('rejection_reason')->nullable()->after('is_cover');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');

            // Technical metadata used by validation and the admin queue.
            $table->unsignedSmallInteger('width')->nullable()->after('reviewed_by');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
            $table->string('file_hash', 64)->nullable()->after('height');
            $table->json('flags')->nullable()->after('file_hash');

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'file_hash']);
        });

        // ── Back-fill existing rows so current live galleries are untouched ──
        DB::table('branch_images')->update([
            'status' => 'approved',
            'source' => 'business',
        ]);

        // First photo of each branch (current sort order) becomes the cover.
        $branchIds = DB::table('branch_images')->distinct()->pluck('branch_id');
        foreach ($branchIds as $branchId) {
            $firstId = DB::table('branch_images')
                ->where('branch_id', $branchId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id');

            if ($firstId) {
                DB::table('branch_images')->where('id', $firstId)->update(['is_cover' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('branch_images', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'status']);
            $table->dropIndex(['branch_id', 'file_hash']);
            $table->dropColumn([
                'status', 'source', 'is_cover', 'rejection_reason',
                'reviewed_at', 'reviewed_by', 'width', 'height', 'file_hash', 'flags',
            ]);
        });
    }
};
