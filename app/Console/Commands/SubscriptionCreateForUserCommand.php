<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Xui\XuiPanelException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SubscriptionCreateForUserCommand extends Command
{
    protected $signature = 'subscription:create-for-user
                            {email : Email зарегистрированного пользователя (ЛК)}
                            {--devices=1 : Лимит устройств (limitIp в XUI + Happ HWID)}
                            {--days=30 : Срок в днях}
                            {--quota-gb=100 : Квота трафика, ГБ (на узел в 3x-ui)}
                            {--ensure-user : Создать пользователя с этим email, если его ещё нет}
                            {--user-name= : Имя для нового пользователя (--ensure-user)}';

    protected $description = 'Создать подписку с заданными параметрами и сразу привязать к пользователю';

    public function handle(CreateDualBundleSubscription $service): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $devices = max(1, (int) $this->option('devices'));
        $days = max(1, (int) $this->option('days'));
        $quotaGb = max(1, (int) $this->option('quota-gb'));

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            if (! (bool) $this->option('ensure-user')) {
                $this->error(
                    'Пользователь не найден. Зарегистрируйтесь на сайте с этим email или запустите с --ensure-user.'
                );

                return self::FAILURE;
            }

            $name = trim((string) $this->option('user-name'));
            if ($name === '') {
                $parts = explode('@', $email, 2);
                $name = $parts[0] !== '' ? $parts[0] : 'Клиент';
            }

            $password = Str::password(26);
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
            $this->warn('Создан новый аккаунт. Одноразовый пароль для входа в ЛК:');
            $this->line($password);
            $this->newLine();
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
