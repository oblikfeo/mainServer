<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 80)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider', 20)->default('wata');
            $table->string('status', 20)->default('created'); // created|pending|paid|declined

            $table->unsignedInteger('amount_rub');
            $table->string('currency', 3)->default('RUB');
            $table->string('description')->nullable();

            $table->string('tariff_plan', 32);
            $table->string('tariff_period', 32);
            $table->unsignedSmallInteger('days');
            $table->unsignedTinyInteger('devices');
            $table->unsignedInteger('quota_gb');

            $table->uuid('provider_link_id')->nullable();
            $table->uuid('provider_transaction_id')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('declined_at')->nullable();

            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['provider', 'provider_link_id']);
            $table->index(['provider', 'provider_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};

