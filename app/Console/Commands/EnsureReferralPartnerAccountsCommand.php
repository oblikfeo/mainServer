<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class EnsureReferralPartnerAccountsCommand extends Command
{
    protected $signature = 'referral:ensure-partners {--force : Создать отсутствующие аккаунты партнёров}';

    protected $description = 'Проверить/создать аккаунты для партнёрских реферальных программ (config/referral.php partners)';

    public function handle(): int
    {
        $partners = (array) config('referral.partners', []);
        if ($partners === []) {
            $this->warn('Партнёры не заданы в config/referral.php');

            return self::SUCCESS;
        }

        $created = 0;
        foreach ($partners as $key => $cfg) {
            if (! is_array($cfg)) {
                continue;
            }

            $email = strtolower(trim((string) ($cfg['referrer_email'] ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error("Партнёр {$key}: некорректный email");

                continue;
            }

            $display = (string) ($cfg['display_name'] ?? $key);
            $existing = User::query()->where('email', $email)->first();
            if ($existing !== null) {
                $this->line("OK {$display}: {$email} (id {$existing->id}, code {$existing->referral_code})");

                continue;
            }

            if (! $this->option('force')) {
                $this->warn("MISSING {$display}: {$email} — запустите с --force для создания");

                continue;
            }

            $local = Str::before($email, '@');
            $password = Str::password(16);
            $user = new User([
                'name' => $local !== '' ? $local : 'partner',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $user->email_verified_at = now();
            $user->save();

            $created++;
            $this->info("CREATED {$display}: {$email} (id {$user->id}, code {$user->referral_code})");
            $this->warn("  Временный пароль: {$password}");
        }

        if ($created > 0) {
            $this->comment("Создано аккаунтов: {$created}. Сохраните пароли и передайте партнёру.");
        }

        return self::SUCCESS;
    }
}
