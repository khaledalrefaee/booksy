<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status_changed_by_type')->nullable()->after('handled_at'); // 'employee' | 'company' | 'owner' | 'customer'
            $table->unsignedBigInteger('status_changed_by_id')->nullable()->after('status_changed_by_type');
            $table->string('status_changed_by_name')->nullable()->after('status_changed_by_id');
            $table->timestamp('status_changed_at')->nullable()->after('status_changed_by_name');
            $table->string('status_previous')->nullable()->after('status_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['status_changed_by_type','status_changed_by_id','status_changed_by_name','status_changed_at','status_previous']);
        });
    }
};
