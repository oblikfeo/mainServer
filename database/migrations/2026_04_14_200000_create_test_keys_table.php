<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_keys', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            /** UUID клиента в 3x-ui */
            $table->uuid('client_uuid')->unique();

            /** Email клиента в 3x-ui (нужен для удаления через API) */
            $table->string('panel_email', 120)->unique();

            /** Служебный subId клиента в панели (удобно для отчёта) */
            $table->string('panel_sub_id', 64)->nullable();

            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at');

            /** Снято админом/крон‑очисткой */
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 120)->nullable();

            /** Снимали ли в панели успешно */
            $table->timestamp('panel_deleted_at')->nullable();

            /** Ключ/ссылка для клиента (как выдавали в момент создания) */
            $table->text('vless_url');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_keys');
    }
};

