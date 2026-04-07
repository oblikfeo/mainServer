<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class SubscriptionClearBoundHwidCommand extends Command
{
    protected $signature = 'subscription:clear-bound-hwid {subscription : subscriptions.id}';

    protected $description = 'Сбросить привязку Happ (bound_hwid_hashes), чтобы клиент мог заново добавить устройства';

    public function handle(): int
    {
        $id = (int) $this->argument('subscription');
        $sub = Subscription::query()->find($id);
        if ($sub === null) {
            $this->error("Подписка #{$id} не найдена.");

            return self::FAILURE;
        }
        $sub->bound_hwid_hashes = null;
        $sub->save();
        $this->info("Сброшено для подписки #{$id}.");

        return self::SUCCESS;
    }
}
