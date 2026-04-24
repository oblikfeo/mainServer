<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('hy2_username', 64)->nullable()->after('nl_sub_id');
            $table->string('hy2_password', 64)->nullable()->after('hy2_username');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['hy2_username', 'hy2_password']);
        });
    }
};
