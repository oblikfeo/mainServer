<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

class SubscriptionAttachUserCommand extends Command
{
    protected $signature = 'subscription:attach-user {subscription : ID подписки в БД} {email : Email зарегистрированного пользователя}';

    protected $description = 'Привязать подписку к пользователю (для ЛК). Пользователь должен уже существовать.';

    public function handle(): int
    {
        $subId = (int) $this->argument('subscription');
        $email = strtolower(trim((string) $this->argument('email')));

        $subscription = Subscription::query()->find($subId);
        if ($subscription === null) {
            $this->error('Подписка с таким ID не найдена.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $this->error('Пользователь не найден. Сначала регистрация на сайте с этим email.');

            return self::FAILURE;
        }

        $subscription->user_id = $user->id;
        $subscription->save();

        $this->info("Подписка #{$subscription->id} привязана к {$user->email}.");

        return self::SUCCESS;
    }
}
