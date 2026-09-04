<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('booking_group_id', 36)->nullable()->index();
            // Booking reference + idempotency key (safe against double-submits).
            $table->string('reference', 20)->nullable()->index();
            $table->string('idempotency_key', 64)->nullable()->index();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            // Whether the customer explicitly picked this staff member vs "any available".
            $table->boolean('employee_requested')->default(false);
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('original_start_time')->nullable();
            $table->unsignedTinyInteger('reschedule_count')->default(0);
            $table->dateTime('rescheduled_at')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->string('status', 32)->default('pending');
            $table->decimal('total_price', 12, 2);
            $table->decimal('tip_amount', 12, 2)->default(0);
            $table->string('payment_status', 32)->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('handled_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('status_changed_by_type')->nullable();
            $table->unsignedBigInteger('status_changed_by_id')->nullable();
            $table->string('status_changed_by_name')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->string('status_previous')->nullable();
            $table->timestamps();

            // Calendar / day-grid / slot queries filter by a scope column +
            // start_time range; these composite indexes cover them. (The
            // resource_id index lives in the resources migration, where the
            // resource_id column is added.)
            $table->index(['company_id', 'start_time'],  'appts_company_start_idx');
            $table->index(['employee_id', 'start_time'], 'appts_employee_start_idx');
            $table->index(['branch_id', 'start_time'],   'appts_branch_start_idx');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
