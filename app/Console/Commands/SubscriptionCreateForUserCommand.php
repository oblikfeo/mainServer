<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Xui\XuiPanelException;
use Illuminate\Console\Command;

class SubscriptionCreateForUserCommand extends Command
{
    protected $signature = 'subscription:create-for-user
                            {email : Email зарегистрированного пользователя (ЛК)}
                            {--devices=1 : Лимит устройств (limitIp в XUI + Happ HWID)}
                            {--days=30 : Срок в днях}
                            {--quota-gb=100 : Квота трафика, ГБ (на узел в 3x-ui; Hy2 получает те же ГБ)}';

    protected $description = 'Создать подписку с заданными параметрами и сразу привязать к пользователю';

    public function handle(CreateDualBundleSubscription $service): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $devices = max(1, (int) $this->option('devices'));
        $days = max(1, (int) $this->option('days'));
        $quotaGb = max(1, (int) $this->option('quota-gb'));

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $this->error('Пользователь не найден. Нужна регистрация на сайте с этим email.');

            return self::FAILURE;
        }

        $this->info("Создаю: {$devices} устр., {$days} дн., {$quotaGb} ГБ → {$email}");

        try {
            $result = $service->create($devices, $days, $quotaGb, (int) $user->id);
        } catch (XuiPanelException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $sub = $result->subscription;
        $this->info("Подписка #{$sub->id}");
        $this->line($result->subscriptionUrl);

        return self::SUCCESS;
    }
}
