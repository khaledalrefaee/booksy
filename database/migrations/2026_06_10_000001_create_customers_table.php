<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->unsignedTinyInteger('age')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('tag', 20)->nullable();
            $table->boolean('is_banned')->default(false);
            $table->string('ban_reason', 255)->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->string('source', 30)->nullable();
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
