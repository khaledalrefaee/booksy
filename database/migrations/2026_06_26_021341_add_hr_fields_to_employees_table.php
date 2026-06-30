<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('contract_type', 20)->nullable()->after('is_bookable');
            $table->date('hire_date')->nullable()->after('contract_type');
            $table->date('contract_end_date')->nullable()->after('hire_date');
            $table->string('national_id', 30)->nullable()->after('contract_end_date');
            $table->string('iban', 40)->nullable()->after('national_id');
            $table->string('bank_name')->nullable()->after('iban');
            $table->string('emergency_contact_name')->nullable()->after('bank_name');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_phone');
            $table->text('qualifications')->nullable()->after('emergency_contact_relation');
            $table->string('license_number', 50)->nullable()->after('qualifications');
            $table->date('license_expiry')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'contract_type', 'hire_date', 'contract_end_date',
                'national_id', 'iban', 'bank_name',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
                'qualifications', 'license_number', 'license_expiry',
            ]);
        });
    }
};
