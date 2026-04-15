<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->json('bound_hwid_meta')->nullable()->after('bound_hwid_hashes');
        });

        Schema::table('test_keys', function (Blueprint $table) {
            $table->json('bound_hwid_meta')->nullable()->after('bound_hwid_hashes');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('bound_hwid_meta');
        });

        Schema::table('test_keys', function (Blueprint $table) {
            $table->dropColumn('bound_hwid_meta');
        });
    }
};

