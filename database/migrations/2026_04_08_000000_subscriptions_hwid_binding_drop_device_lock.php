<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('subscriptions', 'device_limit_locked_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('device_limit_locked_at');
            });
        }

        if (! Schema::hasColumn('subscriptions', 'bound_hwid_hashes')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->json('bound_hwid_hashes')->nullable()->after('devices');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'bound_hwid_hashes')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('bound_hwid_hashes');
            });
        }

        if (! Schema::hasColumn('subscriptions', 'device_limit_locked_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->timestamp('device_limit_locked_at')->nullable()->after('devices');
            });
        }
    }
};
