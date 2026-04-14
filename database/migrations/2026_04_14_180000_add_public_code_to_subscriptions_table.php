<?php

use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('public_code')->nullable()->unique();
        });

        Subscription::query()->whereNull('public_code')->each(function (Subscription $sub) {
            $sub->public_code = Subscription::generateUniquePublicCode();
            $sub->saveQuietly();
        });

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE subscriptions MODIFY public_code INT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['public_code']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('public_code');
        });
    }
};
