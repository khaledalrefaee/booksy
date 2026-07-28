<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable log of every status change an appointment goes through.
 *
 * The appointments table keeps status_previous / status_changed_by_* columns,
 * which only ever remember the last hop. That is enough to render a badge and
 * useless for answering "how long did this customer wait between arriving and
 * being served" — the question an enterprise salon actually asks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            /** null when the appointment was created straight into to_status */
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);

            /** company | employee | customer | system */
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();

            /** mandatory for salon-side cancellations */
            $table->text('reason')->nullable();

            /** true when the scheduler or a side effect made the move */
            $table->boolean('automatic')->default(false);

            /** free-form context: payment method, amount, source screen… */
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();

            /** the timeline of one appointment, in order */
            $table->index(['appointment_id', 'created_at']);
            /** "how many no-shows this month" without scanning appointments */
            $table->index(['company_id', 'to_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_transitions');
    }
};
