<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Subscription\DestroySubscription;
use Illuminate\Console\Command;

class SubscriptionDestroyCommand extends Command
{
    protected $signature = 'subscription:destroy
                            {ids?* : ID подписок в БД (subscriptions.id)}
                            {--last= : Удалить N последних по id подписок вместо списка ids}
                            {--force : Не спрашивать подтверждение}';

    protected $description = 'Удалить подписку(и): клиенты в 3x-ui FI/NL, запись в БД и связанные issued_keys';

    public function handle(DestroySubscription $destroyer): int
    {
        $last = $this->option('last');
        $ids = $this->argument('ids');

        if ($last !== null && $last !== '') {
            $n = max(1, (int) $last);
            $ids = Subscription::query()
                ->orderByDesc('id')
                ->limit($n)
                ->pluck('id')
                ->all();
            if ($ids === []) {
                $this->warn('Подписок в БД нет.');

                return self::SUCCESS;
            }
            $this->info('К удалению (последние '.$n.' по id): '.implode(', ', $ids));
        } elseif ($ids === []) {
            $this->error('Укажите id подписок или --last=N');

            return self::FAILURE;
        }

        $ids = array_map(static fn (int|string $id): int => (int) $id, (array) $ids);

        if (! $this->option('force')) {
            $list = implode(', ', $ids);
            if (! $this->confirm("Удалить подписки id: {$list}? (клиенты в панелях FI/NL и строки в БД)")) {
                return self::SUCCESS;
            }
        }

        foreach ($ids as $id) {
            $sub = Subscription::query()->find($id);
            if ($sub === null) {
                $this->warn("Подписка #{$id} не найдена.");

                continue;
            }

            try {
                $destroyer->destroy($sub);
                $this->info("Удалена подписка #{$id}.");
            } catch (\Throwable $e) {
                $this->error("Ошибка для #{$id}: ".$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
