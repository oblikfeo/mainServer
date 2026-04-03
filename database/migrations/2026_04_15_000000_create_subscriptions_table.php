<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 80)->unique();
            $table->string('fi_sub_id', 64);
            $table->string('nl_sub_id', 64);
            $table->unsignedInteger('quota_gb');
            $table->unsignedBigInteger('expiry_ms');
            $table->unsignedTinyInteger('devices')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
