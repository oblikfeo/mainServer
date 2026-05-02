<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('telegram_bot_blocked_at')->nullable()->after('telegram_linked_at');
        });

        Schema::create('telegram_start_utm_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id');
            $table->string('utm_param', 255);
            $table->timestamps();

            $table->index(['telegram_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_start_utm_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_bot_blocked_at');
        });
    }
};
