<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['wifi_sub_id', 'wifi2_sub_id']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('wifi_sub_id', 64)->nullable()->after('token');
            $table->string('wifi2_sub_id', 64)->nullable()->after('wifi_sub_id');
        });
    }
};
