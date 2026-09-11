<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archive of platform-owner broadcast emails composed in /owner/emails.
 * One row per composed message; the resolved recipient list and the running
 * delivery counters are kept here so the owner can review what was sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable()->index(); // admin who sent
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->string('audience', 20)->default('manual'); // all|active|selected|manual
            $table->json('recipients')->nullable();            // [{email,name}]
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('queued');   // queued|sending|sent|partial|failed
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_emails');
    }
};
