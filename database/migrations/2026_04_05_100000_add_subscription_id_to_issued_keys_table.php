<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issued_keys', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('bundle_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('issued_keys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });
    }
};
