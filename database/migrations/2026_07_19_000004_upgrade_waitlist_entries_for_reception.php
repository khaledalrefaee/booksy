<?php

use App\Enums\WaitlistPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes waitlist_entries usable as a real reception queue.
 *
 * Three gaps and one outright bug:
 *  - no priority, so an urgent walk-in could not be distinguished from someone
 *    happy to come back next week;
 *  - no expected duration, so nobody could tell whether a freed slot was big
 *    enough for the next person in line;
 *  - no expiry, so yesterday's queue stayed on today's screen forever;
 *  - customer_id was an FK to `users` while appointments.customer_id points at
 *    `customers`. WaitlistController::promote() already had to work around this
 *    by re-finding the customer through their phone number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority')
                ->default(WaitlistPriority::Normal->value)
                ->after('status');

            $table->unsignedSmallInteger('estimated_minutes')->nullable()->after('priority');
            $table->dateTime('expires_at')->nullable()->after('preferred_start');
        });

        /* ── repoint customer_id at the CRM table ── */
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        // Any existing ids referenced `users` and are meaningless against
        // `customers`; the rows keep customer_name/customer_phone, which is what
        // promote() actually reads.
        DB::table('waitlist_entries')->update(['customer_id' => null]);

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();

            // The queue's only ordering: urgent first, then longest waiting.
            $table->index(['company_id', 'branch_id', 'status', 'priority'], 'waitlist_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropIndex('waitlist_queue_idx');
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['priority', 'estimated_minutes', 'expires_at']);
        });

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
