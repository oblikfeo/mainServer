<?php

namespace App\Console\Commands;

use App\Mail\MassInviteMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MassInviteTestMailCommand extends Command
{
    protected $signature = 'mass-invite:test-mail
                            {--to= : Кому отправить (если пусто — mass_invite.test_recipient)}
                            {--example= : Подставить в поле «логин» этот email (по умолчанию = адрес получателя)}';

    protected $description = 'Одна отправка пригласительного письма: по умолчанию на test_recipient, или --to=email для конкретного адреса.';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));
        if ($to === '') {
            $to = (string) config('mass_invite.test_recipient', 'kfc.kurochka@gmail.com');
        }
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Некорректный получатель: задайте --to=email или mass_invite.test_recipient в конфиге.');

            return self::FAILURE;
        }

        /** В рассылке в теле письма — email для входа. */
        $loginInBody = trim((string) $this->option('example'));
        if ($loginInBody === '') {
            $loginInBody = $to;
        }

        if (! filter_var($loginInBody, FILTER_VALIDATE_EMAIL)) {
            $this->error('Некорректный --example= email.');

            return self::FAILURE;
        }

        $recipients = config('mass_invite.recipients', []);

        $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
        $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
        $fromName = (string) ($brand.' · поддержка');
        $appUrl = rtrim((string) config('app.url'), '/');
        $forgotPasswordUrl = url(route('password.request', [], false));

        Mail::to($to)->send(new MassInviteMail(
            loginEmail: $loginInBody,
            brand: $brand,
            supportFromAddress: $fromAddress,
            supportFromName: $fromName,
            forgotPasswordUrl: $forgotPasswordUrl,
            appUrl: $appUrl,
            supportEmail: $fromAddress,
        ));

        $this->info("Отправлено: {$to} · логин в письме: {$loginInBody} · в recipients: ".count($recipients).'.');

        return self::SUCCESS;
    }
}
