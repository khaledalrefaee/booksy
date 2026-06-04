<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staff accounts: tied to company; branch_id null = company-wide (e.g. finance). role_id drives UI/API access.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
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
