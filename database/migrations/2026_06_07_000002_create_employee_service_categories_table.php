<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: which service categories an employee can handle.
     * Used on the booking flow so customers know what each employee offers.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('employee_service_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();
            $table->foreignId('service_category_id')
                  ->constrained('service_categories')
                  ->cascadeOnDelete();

            $table->unique(['employee_id', 'service_category_id'], 'emp_svc_cat_unique');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_service_categories');
    }
};
