<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Xui\XuiSubscriptionLimitIpSync;
use Illuminate\Console\Command;

class SubscriptionSyncPanelLimitIpCommand extends Command
{
    protected $signature = 'subscription:sync-panel-limit-ip {--id=* : Только эти subscriptions.id; пусто — все}';

    protected $description = 'Обновить limitIp у клиентов FI/NL в 3x-ui из поля devices (после смены логики или ручного правления в БД)';

    public function handle(XuiSubscriptionLimitIpSync $sync): int
    {
        $ids = $this->option('id');
        $q = Subscription::query()->orderBy('id');
        if (is_array($ids) && $ids !== []) {
            $q->whereIn('id', array_map('intval', $ids));
        }

        $n = 0;
        foreach ($q->cursor() as $sub) {
            $sync->syncForSubscription($sub);
            $n++;
            $this->line("#{$sub->id}: limitIp ← {$sub->devices}");
        }

        $this->info("Готово, обработано: {$n}");

        return self::SUCCESS;
    }
}
