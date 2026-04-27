<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_grants', function (Blueprint $table) {
            $table->id();
            $table->string('grant_key', 120)->unique();
            $table->string('kind', 64);
            $table->foreignId('beneficiary_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('referral_test_credit_hours')->default(0)->after('referred_by');
            $table->decimal('referral_subscription_credit_days', 8, 2)->default(0)->after('referral_test_credit_hours');
            $table->unsignedTinyInteger('referral_pending_extra_devices')->default(0)->after('referral_subscription_credit_days');
            $table->boolean('referral_pending_unlimited_traffic')->default(false)->after('referral_pending_extra_devices');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'referral_test_credit_hours',
                'referral_subscription_credit_days',
                'referral_pending_extra_devices',
                'referral_pending_unlimited_traffic',
            ]);
        });
        Schema::dropIfExists('referral_grants');
    }
};
