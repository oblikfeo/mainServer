<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Откуда подарочные дни (users.promo_bonus_days), если не от промокода регистрации:
 * например «акция Возвращайтесь» (24.11.2026). Пишется в описание заказа, сбрасывается вместе с днями.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('promo_bonus_note', 64)->nullable()->after('promo_bonus_days');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('promo_bonus_note');
        });
    }
};
