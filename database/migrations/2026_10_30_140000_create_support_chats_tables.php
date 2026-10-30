<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Чат поддержки на главной (виджет): диалог с ассистентом, при необходимости — перевод на оператора
 * в группу поддержки Telegram. Ответ оператора (reply в группе) возвращается в виджет.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_chats', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 48)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('ai'); // ai | operator
            $table->string('contact', 191)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('page', 255)->nullable();
            $table->unsignedBigInteger('tg_card_message_id')->nullable();
            $table->timestamp('handoff_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index('tg_card_message_id');
        });

        Schema::create('support_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_chat_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16); // user | assistant | operator | system
            $table->text('content');
            $table->unsignedBigInteger('tg_message_id')->nullable();
            $table->string('operator_name', 64)->nullable();
            $table->timestamps();

            $table->index('tg_message_id');
            $table->index(['support_chat_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_messages');
        Schema::dropIfExists('support_chats');
    }
};
