<?php

use App\Models\TestKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function makeToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    public function up(): void
    {
        Schema::table('test_keys', function (Blueprint $table) {
            $table->string('token', 80)->nullable()->unique()->after('panel_sub_id');
            $table->text('subscription_url')->nullable()->after('vless_url');
            $table->unsignedInteger('quota_gb')->default(50)->after('subscription_url');
            $table->unsignedTinyInteger('limit_ip')->default(1)->after('quota_gb');
        });

        $appUrl = rtrim((string) config('app.url'), '/');

        TestKey::query()->orderBy('id')->each(function (TestKey $row) use ($appUrl): void {
            if (! filled($row->token)) {
                do {
                    $token = $this->makeToken();
                } while (TestKey::query()->where('token', $token)->exists());
                $row->token = $token;
            }

            if (! filled($row->subscription_url)) {
                $row->subscription_url = $appUrl.'/sub/'.$row->token;
            }

            if ((int) ($row->quota_gb ?? 0) < 1) {
                $row->quota_gb = (int) config('test_keys.default_quota_gb', 50);
            }

            if ((int) ($row->limit_ip ?? 0) < 1) {
                $row->limit_ip = (int) config('test_keys.default_limit_ip', 1);
            }

            $row->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('test_keys', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'subscription_url', 'quota_gb', 'limit_ip']);
        });
    }
};

