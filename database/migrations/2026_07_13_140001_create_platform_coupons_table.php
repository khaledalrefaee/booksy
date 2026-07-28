<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->string('type', 16);                    // percent | fixed
            $table->decimal('value', 12, 2);
            $table->string('currency', 10)->nullable();    // required for fixed
            $table->json('company_ids')->nullable();       // null = every company
            $table->json('plan_ids')->nullable();          // null = every plan
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('notes')
                ->constrained('platform_coupons')->nullOnDelete();
            $table->string('coupon_code', 40)->nullable()->after('coupon_id');
            $table->decimal('list_price', 12, 2)->nullable()->after('coupon_code');
            $table->decimal('discount_amount', 12, 2)->nullable()->after('list_price');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'list_price', 'discount_amount']);
        });

        Schema::dropIfExists('platform_coupons');
    }
};
