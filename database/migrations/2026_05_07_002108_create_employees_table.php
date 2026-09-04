<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            // Access axes: role permissions apply on every branch / holds all permissions.
            $table->boolean('all_branches')->default(false);
            $table->boolean('full_access')->default(false);
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bookable')->default(false);
            // HR / contract fields
            $table->string('contract_type', 20)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->unsignedSmallInteger('annual_leave_days')->default(21);
            // Offboarding: resignation / termination record
            $table->date('termination_date')->nullable();
            $table->string('termination_type', 20)->nullable();
            $table->text('termination_reason')->nullable();
            $table->string('national_id', 30)->nullable();
            $table->string('iban', 40)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->text('qualifications')->nullable();
            $table->string('license_number', 50)->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['company_id', 'email']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
