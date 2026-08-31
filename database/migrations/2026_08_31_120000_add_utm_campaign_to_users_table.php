<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Метка рекламной кампании на пользователе (first-touch).
 *
 * До этого метка из t.me/{bot}?start=МЕТКА оседала только в telegram_start_utm_logs
 * и не связывалась с аккаунтом — из-за чего покупку нельзя было отнести к кампании.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('utm_campaign', 64)->nullable()->after('referred_by');
            $table->timestamp('utm_campaign_at')->nullable()->after('utm_campaign');

            $table->index('utm_campaign');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['utm_campaign']);
            $table->dropColumn(['utm_campaign', 'utm_campaign_at']);
        });
    }
};
