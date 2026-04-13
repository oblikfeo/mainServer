<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetUsersEmailVerificationCommand extends Command
{
    protected $signature = 'users:reset-email-verification
                            {--force : Выполнить без подтверждения}';

    protected $description = 'Сбросить подтверждение почты у всех пользователей и очистить коды';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Сбросить email_verified_at и коды у всех пользователей?')) {
            return self::FAILURE;
        }

        $n = User::query()->update([
            'email_verified_at' => null,
            'email_verification_code_hash' => null,
            'email_verification_code_sent_at' => null,
        ]);

        $this->info("Обновлено записей: {$n}");

        return self::SUCCESS;
    }
}
