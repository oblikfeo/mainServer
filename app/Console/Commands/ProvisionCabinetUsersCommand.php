<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionCabinetUsersCommand extends Command
{
    protected $signature = 'subscription:provision-cabinet-users
        {file : Путь к файлу: строки «URL_подписки email пароль» (через пробел, пароль — всё после email)}
        {--dry-run : Только показать действия}';

    protected $description = 'Создаёт пользователей и привязывает подписки по токену из URL; строка ADMIN email пароль — для подписки, которой нет в списке URL';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_readable($path)) {
            $this->error('Файл не найден или не читается: '.$path);

            return self::FAILURE;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->error('Не удалось прочитать файл.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $knownTokens = [];
        $adminEmail = null;
        $adminPassword = null;

        foreach ($lines as $num => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^ADMIN\s+(\S+)\s+(\S+)\s*$/i', $line, $m)) {
                $adminEmail = $m[1];
                $adminPassword = $m[2];

                continue;
            }

            if (! preg_match('/^(\S+)\s+(\S+)\s+(.+)$/', $line, $m)) {
                $this->warn('Строка '.($num + 1).': пропуск (ожидается: URL email пароль)');

                continue;
            }

            $urlOrToken = $m[1];
            $email = strtolower($m[2]);
            $password = $m[3];

            $token = $this->extractToken($urlOrToken);
            if ($token === '') {
                $this->warn('Строка '.($num + 1).': не извлечён токен');

                continue;
            }

            $knownTokens[] = $token;

            $subscription = Subscription::query()->where('token', $token)->first();
            if ($subscription === null) {
                $this->error('Подписка с токеном …'.Str::take($token, 8).' не найдена в БД.');

                return self::FAILURE;
            }

            if ($dry) {
                $this->line("[dry-run] {$email} → подписка #{$subscription->id}");

                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => Str::before($email, '@') ?: 'user',
                    'password' => Hash::make($password),
                ]
            );

            $subscription->user_id = $user->id;
            $subscription->save();

            $this->info("OK: {$email} → подписка #{$subscription->id}");
        }

        if ($adminEmail === null || $adminPassword === null) {
            $this->error('В конце файла нужна строка: ADMIN email пароль (для подписки, которой нет в списке URL).');

            return self::FAILURE;
        }

        $adminEmail = strtolower($adminEmail);
        $orphan = Subscription::query()
            ->whereNotIn('token', array_unique($knownTokens))
            ->orderBy('id')
            ->first();

        if ($orphan === null) {
            $this->error('Не найдена «лишняя» подписка (все токены из файла совпали с единственными записями или БД пуста).');

            return self::FAILURE;
        }

        if ($dry) {
            $this->line("[dry-run] ADMIN {$adminEmail} → подписка #{$orphan->id} (токен не из списка)");

            return self::SUCCESS;
        }

        $adminUser = User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'admin',
                'password' => Hash::make($adminPassword),
            ]
        );

        $orphan->user_id = $adminUser->id;
        $orphan->save();

        $this->info("OK: ADMIN {$adminEmail} → подписка #{$orphan->id} (админская)");

        return self::SUCCESS;
    }

    private function extractToken(string $urlOrToken): string
    {
        if (str_contains($urlOrToken, '/sub/')) {
            $path = parse_url($urlOrToken, PHP_URL_PATH) ?? '';

            return Str::after($path, '/sub/');
        }

        return $urlOrToken;
    }
}
