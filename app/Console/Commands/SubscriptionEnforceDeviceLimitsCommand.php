<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Xui\XuiSubscriptionConnectionInspector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SubscriptionEnforceDeviceLimitsCommand extends Command
{
    protected $signature = 'subscription:enforce-device-limits';

    protected $description = 'Проверка уникальных IP по подписке (FI+NL); при превышении лимита отключает клиентов в 3x-ui';

    public function handle(XuiSubscriptionConnectionInspector $inspector): int
    {
        if (! config('xui.enforce_device_limits', false)) {
            return self::SUCCESS;
        }

        $payload = $inspector->inspectAllActive();
        foreach ($payload['errors'] as $err) {
            $this->warn($err);
        }

        foreach ($payload['by_subscription_id'] as $subId => $row) {
            $sub = Subscription::query()->find($subId);
            if ($sub === null) {
                continue;
            }

            if ($row['over']) {
                $inspector->setSubscriptionClientsEnabled($sub, false);
                Log::warning('subscription.device_limit.exceeded', [
                    'subscription_id' => $subId,
                    'online_ip_count' => $row['online_ip_count'],
                    'limit' => $row['limit'],
                ]);
                $this->line("Подписка #{$subId}: превышение ({$row['online_ip_count']} IP > {$row['limit']}) — клиенты отключены.");
            } elseif (config('xui.auto_reenable_clients_within_limit', true)) {
                $inspector->setSubscriptionClientsEnabled($sub, true);
            }
        }

        return self::SUCCESS;
    }
}
