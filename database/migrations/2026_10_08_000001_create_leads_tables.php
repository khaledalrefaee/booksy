<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Pre-launch lead capture (CRM layer 0).
 *
 *  leads            one row per BUSINESS/PERSON that raised their hand. A repeat
 *                   submission updates this row (see phone_normalized) instead of
 *                   inserting a twin, and bumps interaction_count.
 *  lead_activities  append-only timeline: created, resubmitted, status change, note…
 *  lead_visits      one row per unique visitor per funnel page, with the same
 *                   attribution columns as leads → conversion rate by source/campaign.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Contact
            $table->string('full_name', 120);
            $table->string('phone', 40);
            // Canonical digits (963944123456) — the duplicate key. Unique so two
            // simultaneous submissions can never both insert.
            $table->string('phone_normalized', 20)->unique();
            $table->string('whatsapp', 40)->nullable();
            $table->string('email', 150)->nullable()->index();

            // Business
            $table->string('business_name', 150);
            $table->string('business_type', 40)->index();
            $table->string('city', 80)->index();
            $table->string('area', 100)->nullable()->index();
            $table->unsignedSmallInteger('number_of_branches')->default(1)->index();
            $table->string('website', 255)->nullable();
            $table->string('instagram', 255)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->json('interests')->nullable();

            // Attribution (captured automatically, never typed by the visitor)
            $table->string('source', 40)->default('direct')->index();
            $table->string('campaign', 80)->nullable()->index();
            $table->string('landing_page', 255)->nullable();
            $table->string('referral', 255)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();

            // Pipeline
            $table->string('status', 30)->default('new')->index();
            $table->unsignedInteger('interaction_count')->default(1);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->foreignId('contacted_by')->nullable()->constrained('owners')->nullOnDelete();

            // Context
            $table->string('locale', 5)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            // Null = the visitor / the system acted, not a team member.
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->string('type', 30);            // created | resubmitted | reopened | status_changed | note
            $table->text('body')->nullable();      // note text
            $table->json('meta')->nullable();      // {from,to} for status, submitted snapshot for resubmits
            $table->timestamp('created_at')->useCurrent();

            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('lead_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 40);       // random per browser session, not personal
            $table->string('page', 20);             // welcome | join | business
            $table->string('source', 40)->default('direct');
            $table->string('campaign', 80)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('landing_page', 255)->nullable();
            $table->string('referral_host', 120)->nullable();
            $table->string('device', 10)->nullable();   // mobile | tablet | desktop
            $table->string('locale', 5)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['visitor_id', 'page']);
            $table->index('created_at');
            $table->index(['source', 'created_at']);
            $table->index('campaign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_visits');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
