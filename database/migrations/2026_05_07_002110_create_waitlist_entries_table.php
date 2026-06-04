<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reception queue / walk-ins: convert to appointment by setting appointment_id.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('preferred_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            /** waiting | contacted | cancelled | booked */
            $table->string('status', 32)->default('waiting');
            $table->dateTime('preferred_start')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('handled_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
