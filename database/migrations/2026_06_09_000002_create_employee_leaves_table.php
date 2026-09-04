<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // deduction_id references employee_deductions, which is created after this
        // migration — disable FK checks so the constraint can be declared inline.
        Schema::disableForeignKeyConstraints();

        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type', 20)->default('annual');
            $table->boolean('is_hourly')->default(false);
            $table->time('start_hour')->nullable();
            $table->time('end_hour')->nullable();
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable(); // manager notes
            // Optional salary deduction attached to the leave; the actual
            // EmployeeDeduction row is created when the leave is approved.
            $table->decimal('deduction_amount', 12, 2)->nullable();
            $table->string('deduction_currency', 5)->nullable();
            $table->foreignId('deduction_id')->nullable()->constrained('employee_deductions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leaves');
    }
};
