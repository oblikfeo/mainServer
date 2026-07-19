<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_chat_messages', function (Blueprint $table) {
            $table->id();
            // Кто писал боту (идентификатор Telegram — есть всегда).
            $table->unsignedBigInteger('telegram_user_id');
            $table->string('telegram_username')->nullable();
            // Привязанный аккаунт (email виден через users), если есть.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Реплика: клиент или ассистент.
            $table->string('role', 16);
            $table->text('content');
            // Была ли на этой реплике эскалация на живого оператора.
            $table->boolean('handoff')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['telegram_user_id', 'id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_chat_messages');
    }
};
