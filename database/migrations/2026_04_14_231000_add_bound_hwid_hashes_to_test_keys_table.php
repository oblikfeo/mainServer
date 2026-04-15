<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_keys', function (Blueprint $table) {
            $table->json('bound_hwid_hashes')->nullable()->after('limit_ip');
        });
    }

    public function down(): void
    {
        Schema::table('test_keys', function (Blueprint $table) {
            $table->dropColumn('bound_hwid_hashes');
        });
    }
};

