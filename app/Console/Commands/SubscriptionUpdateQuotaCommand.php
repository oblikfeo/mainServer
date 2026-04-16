<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Xui\XuiSubscriptionQuotaSync;
use Illuminate\Console\Command;

class SubscriptionUpdateQuotaCommand extends Command
{
    protected $signature = 'subscription:update-quota {token : Токен из /sub/{token}} {quota_gb : Новая квота в ГБ}';

    protected $description = 'Обновить quota_gb у подписки и синхронизировать totalGB в 3x-ui (FI/NL) по subId';

    public function handle(XuiSubscriptionQuotaSync $sync): int
    {
        $token = trim((string) $this->argument('token'));
        $token = preg_replace('~^https?://[^/]+/sub/~', '', $token) ?? $token;
        $token = trim($token, "/ \t\n\r\0\x0B");

        $quotaGb = (int) $this->argument('quota_gb');
        if ($token === '' || $quotaGb < 1) {
            $this->error('Неверные параметры.');

            return self::FAILURE;
        }

        $sub = Subscription::query()->where('token', $token)->first();
        if (! $sub) {
            $this->error('Подписка не найдена по token.');

            return self::FAILURE;
        }

        $sub->quota_gb = $quotaGb;
        $sub->save();

        $sync->syncForSubscription($sub);

        $this->info("Готово: subscription #{$sub->id}, quota_gb = {$quotaGb} (на узел: ".$sub->perNodeTotalBytes()." байт)");

        return self::SUCCESS;
    }
}

