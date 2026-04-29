<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_id')->nullable()->unique();
            $table->string('telegram_username')->nullable();
            $table->timestamp('telegram_linked_at')->nullable();
        });

        Schema::create('telegram_link_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('otp_code_hash', 255)->nullable();
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->bigInteger('telegram_chat_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_link_sessions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_id', 'telegram_username', 'telegram_linked_at']);
        });
    }
};
