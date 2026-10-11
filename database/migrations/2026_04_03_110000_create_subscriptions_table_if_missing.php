<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Исторически create_subscriptions_table датирован позже первых ALTER этой таблицы,
 * поэтому на чистой базе (тесты) миграции падали. На бою таблица уже есть — здесь no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 80)->unique();
            $table->string('fi_sub_id', 64);
            $table->string('nl_sub_id', 64);
            $table->unsignedInteger('quota_gb');
            $table->unsignedBigInteger('expiry_ms');
            $table->unsignedTinyInteger('devices')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Таблицу удаляет исходная миграция create_subscriptions_table.
    }
};
