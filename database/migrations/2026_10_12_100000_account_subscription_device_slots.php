<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «Аккаунт = подписка»: доп. устройства живут отдельными слотами со своим счётчиком,
 * архивные подписки помнят прежнего владельца, заказ хранит срок оплаты устройств.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            // Сколько оплаченного времени было на момент anchor_ms; тикает только пока подписка активна.
            $table->unsignedBigInteger('paid_ms_left');
            $table->unsignedBigInteger('anchor_ms');
            $table->foreignId('payment_order_id')->nullable()->constrained('payment_orders')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('archived_from_user_id')->nullable()->after('user_id');
            $table->timestamp('archived_at')->nullable()->after('archived_from_user_id');
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('device_months')->default(0)->after('devices');
        });

        // На бою индекс ставит команда account:migrate после чистки данных; на чистой базе — сразу.
        $hasDuplicates = DB::table('subscriptions')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if (! $hasDuplicates) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->unique('user_id', 'subscriptions_user_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('subscriptions', 'subscriptions_user_id_unique')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropUnique('subscriptions_user_id_unique');
            });
        }
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('device_months');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['archived_from_user_id', 'archived_at']);
        });
        Schema::dropIfExists('device_slots');
    }
};
