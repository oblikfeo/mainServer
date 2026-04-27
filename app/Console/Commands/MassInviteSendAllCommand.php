<?php

namespace App\Console\Commands;

use App\Mail\MassInviteMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MassInviteSendAllCommand extends Command
{
    protected $signature = 'mass-invite:send-all
                            {--force : Выполнить рассылку (без флага — не отправляем)}
                            {--dry-run : Показать список адресов, без отправки}
                            {--min-delay=1 : Мин. пауза между письмами (секунды)}
                            {--max-delay=3 : Макс. пауза между письмами (секунды)}';

    protected $description = 'Рассылка письма «вход в кабинет» всем mass_invite.recipients, пауза 1–3 с между отправками (настраивается).';

    public function handle(): int
    {
        $list = config('mass_invite.recipients', []);
        if ($list === []) {
            $this->error('Список mass_invite.recipients пуст.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Сухой прогон — отправки нет. Адреса:');
            foreach ($list as $i => $email) {
                $this->line((string) ($i + 1).'. '.(string) $email);
            }

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->warn('Рассылка не запущена. Просмотр: --dry-run. Отправка: --force');
            $this->line('Адресов: '.count($list));

            return self::FAILURE;
        }

        $minDelay = max(0, (int) $this->option('min-delay'));
        $maxDelay = max($minDelay, (int) $this->option('max-delay'));
        if ($minDelay > 60 || $maxDelay > 60) {
            $this->error('Пауза слишком большая (макс. 60 с).');

            return self::FAILURE;
        }

        $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
        $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
        $fromName = (string) ($brand.' · поддержка');
        $appUrl = rtrim((string) config('app.url'), '/');
        $forgotPasswordUrl = url(route('password.request', [], false));

        $mailableFactory = function (string $toEmail) use ($brand, $fromAddress, $fromName, $appUrl, $forgotPasswordUrl): MassInviteMail {
            return new MassInviteMail(
                loginEmail: $toEmail,
                brand: $brand,
                supportFromAddress: $fromAddress,
                supportFromName: $fromName,
                forgotPasswordUrl: $forgotPasswordUrl,
                appUrl: $appUrl,
                supportEmail: $fromAddress,
            );
        };

        $n = count($list);
        $ok = 0;
        $fail = 0;

        foreach ($list as $i => $email) {
            $email = (string) $email;
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error("Пропуск (некорректный email): {$email}");
                $fail++;

                continue;
            }

            if ($i > 0) {
                $micros = random_int($minDelay * 1_000_000, $maxDelay * 1_000_000);
                usleep($micros);
            }

            try {
                Mail::to($email)->send($mailableFactory($email));
                $ok++;
                $this->line('['.($i + 1).'/'.$n.'] '.$email.' — отправлено');
            } catch (Throwable $e) {
                $fail++;
                $this->error('['.($i + 1).'/'.$n.'] '.$email.' — ошибка: '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Готово: успешно {$ok}, с ошибками {$fail}.");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
