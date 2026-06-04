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
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            /** pending | confirmed | rejected | cancelled | completed | no_show */
            $table->string('status', 32)->default('pending');
            $table->decimal('total_price', 12, 2);
            /** pending | paid | partial | refunded | waived */
            $table->string('payment_status', 32)->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('handled_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
