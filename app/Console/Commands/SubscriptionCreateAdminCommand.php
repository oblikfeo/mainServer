<?php

namespace App\Console\Commands;

use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Xui\XuiPanelException;
use Illuminate\Console\Command;

/**
 * Админские подписи без лимита устройств (devices=0 → HWID-гейт отключён, limitIp=0 на панели),
 * огромный трафик и срок на стороне 3x-ui; в БД expiry_ms=0 — «без срока» в логике приложения.
 */
class SubscriptionCreateAdminCommand extends Command
{
    protected $signature = 'subscription:create-admin
                            {count=3 : Сколько подписок создать}
                            {--days=36525 : Срок на панели 3x-ui, дней (~по умолчанию ~100 лет)}
                            {--quota-gb=500000 : Квота GB на подписку (суммарно; делится по узлам)}';

    protected $description = 'Создать админские подписки (без лимита устройств, максимальный трафик и срок на панели)';

    public function handle(CreateDualBundleSubscription $service): int
    {
        $count = max(1, (int) $this->argument('count'));
        $days = max(1, (int) $this->option('days'));
        $quotaGb = max(1, (int) $this->option('quota-gb'));

        $devices = 0;

        $baseUrl = rtrim((string) config('app.url'), '/');

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Создание подписки {$i}/{$count}…");

            try {
                $result = $service->create($devices, $days, $quotaGb, null);
            } catch (XuiPanelException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $sub = $result->subscription;
            $sub->expiry_ms = 0;
            $sub->save();

            $url = $baseUrl.'/sub/'.$sub->token;
            $this->line("  ID: {$sub->id}");
            $this->line("  URL: {$url}");
            $this->newLine();
        }

        $this->info('Готово. devices=0: без лимита устройств и без проверки HWID; квота и срок заданы на панели 3x-ui.');

        return self::SUCCESS;
    }
}
