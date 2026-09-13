<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field-sales visit log. Owned by the GlowRez platform (owner_id = the staff
 * member who filed it), never by a client company.
 *
 * The columns split into three groups:
 *   1. Sales content  — what the rep captures and sees (place, contact, notes…).
 *   2. Presence signals — captured quietly at submit (GPS, server time, photo,
 *      device) so the record is trustworthy without the rep feeling watched.
 *   3. Verification    — manager-only fields (confidence score, flags, review).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->index(); // staff member (owners.id)

            // ── 1. Sales content ─────────────────────────────────────────
            $table->string('place_name');
            $table->string('area')->nullable();          // typed / confirmed region
            $table->string('place_type', 40)->nullable(); // salon, spa, clinic, gym, barber, other
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->boolean('explained_glowrez')->nullable();
            $table->string('current_system')->nullable(); // system they use today
            $table->text('problems')->nullable();         // pains they mentioned
            $table->text('opinion')->nullable();          // their view on the idea
            $table->unsignedTinyInteger('interest_level')->nullable(); // 1..5
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();

            // ── 2. Presence signals (captured silently) ──────────────────
            $table->dateTime('visited_at');             // server time of check-in
            $table->dateTime('checked_out_at')->nullable();
            $table->unsignedInteger('dwell_seconds')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->float('gps_accuracy')->nullable();   // metres
            $table->decimal('checkout_lat', 10, 7)->nullable();
            $table->decimal('checkout_lng', 10, 7)->nullable();
            $table->float('checkout_accuracy')->nullable();
            $table->boolean('gps_denied')->default(false);
            $table->string('resolved_address')->nullable(); // reverse geocoded
            $table->string('photo_path')->nullable();
            $table->dateTime('photo_taken_at')->nullable(); // from EXIF when present
            $table->string('source', 16)->default('web');    // mobile | desktop | web
            $table->string('user_agent')->nullable();
            $table->string('ip', 45)->nullable();

            // ── 3. Verification (manager-only) ───────────────────────────
            $table->json('verification')->nullable();    // raw signal breakdown
            $table->unsignedTinyInteger('confidence_score')->nullable(); // 0..100
            $table->boolean('flagged')->default(false);
            $table->json('flag_reasons')->nullable();
            $table->string('review_status', 16)->default('pending'); // pending|approved|rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_note')->nullable();

            $table->timestamps();

            $table->index(['owner_id', 'visited_at']);
            $table->index('review_status');
            $table->index('flagged');
            $table->index('follow_up_date');
            $table->foreign('owner_id')->references('id')->on('owners')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('owners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_visits');
    }
};
