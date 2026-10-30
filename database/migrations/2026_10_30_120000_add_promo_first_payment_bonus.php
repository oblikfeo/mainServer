<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Подарок к первой оплате по промокоду (OCT31: +30 дней).
 *
 * users.promo_bonus_days — сколько дней ждёт первой оплаты подписки (0 — подарка нет или уже выдан).
 * payment_orders.bonus_days — сколько подарочных дней добавлено к этому заказу.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('promo_bonus_days')->default(0)->after('promo_welcome_pending');
        });
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('bonus_days')->default(0)->after('days');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('bonus_days');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('promo_bonus_days');
        });
    }
};
