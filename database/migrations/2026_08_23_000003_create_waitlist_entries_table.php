<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online booking waitlist — a customer who couldn't find a slot asks to be told
 * when one opens. Kept SEPARATE from `waitlist_entries` (the in-salon reception
 * queue), which is a different concept with its own reception board.
 *
 * When a matching appointment is cancelled, the oldest waiting entry for that
 * branch+service+day (within its optional time window) is notified with a link
 * to book the freed time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('preferred_date');
            $table->time('pref_from')->nullable();   // null = any time that day
            $table->time('pref_to')->nullable();
            $table->string('status', 20)->default('waiting'); // waiting|notified|booked|expired|cancelled
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('hold_until')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'preferred_date', 'status'], 'bwl_match_idx');
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_waitlist');
    }
};
