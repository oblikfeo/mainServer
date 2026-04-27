<?php

namespace App\Console\Commands;

use App\Mail\MassInviteMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MassInviteTestMailCommand extends Command
{
    protected $signature = 'mass-invite:test-mail
                            {--example= : Email для примера «ваш логин» (по умолчанию первый из config/mass_invite.php)}';

    protected $description = 'Отправить одно письмо «вход в кабинет» на mass_invite.test_recipient (пример логина: --example или первый из списка recipients).';

    public function handle(): int
    {
        $recipients = config('mass_invite.recipients', []);
        if ($recipients === []) {
            $this->error('Список mass_invite.recipients пуст.');

            return self::FAILURE;
        }

        $example = trim((string) $this->option('example'));
        if ($example === '') {
            $example = (string) ($recipients[0] ?? '');
        }

        if ($example === '' || ! filter_var($example, FILTER_VALIDATE_EMAIL)) {
            $this->error('Укажите корректный --example=email или заполните список в config/mass_invite.php.');

            return self::FAILURE;
        }

        $to = (string) config('mass_invite.test_recipient', 'kfc.kurochka@gmail.com');
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Некорректный mass_invite.test_recipient.');

            return self::FAILURE;
        }

        $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
        $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
        $fromName = (string) ($brand.' · поддержка');
        $appUrl = rtrim((string) config('app.url'), '/');
        $forgotPasswordUrl = url(route('password.request', [], false));

        Mail::to($to)->send(new MassInviteMail(
            loginEmail: $example,
            brand: $brand,
            supportFromAddress: $fromAddress,
            supportFromName: $fromName,
            forgotPasswordUrl: $forgotPasswordUrl,
            appUrl: $appUrl,
            supportEmail: $fromAddress,
        ));

        $this->info("Письмо отправлено на: {$to} (в письме логин: {$example}).");
        $this->line('Всего в списке recipients: '.count($recipients).' адресов (массовая рассылка по ним не выполнялась).');

        return self::SUCCESS;
    }
}
