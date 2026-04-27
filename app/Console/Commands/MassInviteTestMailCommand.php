<?php

namespace App\Console\Commands;

use App\Mail\MassInviteMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MassInviteTestMailCommand extends Command
{
    protected $signature = 'mass-invite:test-mail
                            {--example= : Подставить в поле «логин» этот email (по умолчанию = адрес mass_invite.test_recipient — как при реальной рассылке своему ящику)}';

    protected $description = 'Одна отправка на mass_invite.test_recipient: в письме логин = получатель, если не задан --example.';

    public function handle(): int
    {
        $to = (string) config('mass_invite.test_recipient', 'kfc.kurochka@gmail.com');
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Некорректный mass_invite.test_recipient.');

            return self::FAILURE;
        }

        /** В реальной рассылке у каждого в теле письма будет его email. Для теста на свой ящик — тот же адрес, что и test_recipient. */
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
