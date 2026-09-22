<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One row per company per day the Daily Business Summary email is sent.
     *
     * The unique (company_id, summary_date) key is the guardrail that stops the
     * scheduler from mailing the same company twice on the same day: the send
     * command inserts the row inside a transaction *before* dispatching the
     * mail, and a duplicate insert simply throws and is skipped.
     *
     * `summary_date` is the company-local calendar date (the report's day), not
     * a UTC/server date, so it stays correct once per-company timezones exist.
     */
    public function up(): void
    {
        Schema::create('daily_summary_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'summary_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_summary_logs');
    }
};
