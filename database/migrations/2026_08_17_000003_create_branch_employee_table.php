<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L2 — Branch access. The set of branches an employee may operate in.
 * Ignored (superseded) when employees.all_branches = true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_employee', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['branch_id', 'employee_id']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_employee');
    }
};
