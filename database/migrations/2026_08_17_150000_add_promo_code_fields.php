<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_promo_code', 32)->nullable();
            $table->boolean('promo_welcome_pending')->default(false);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('promo_code', 32)->nullable()->after('is_trial');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('promo_code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['registration_promo_code', 'promo_welcome_pending']);
        });
    }
};
