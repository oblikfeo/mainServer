<?php

namespace App\Console\Commands;

use App\Mail\TrialFollowupMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialFollowupTestMailCommand extends Command
{
    protected $signature = 'trial-followup:test-mail {--to= : Email получателя (обязательно)}';

    protected $description = 'Отправить превью follow-up письма после триала (без выдачи ключа).';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Укажите корректный --to=email.');

            return self::FAILURE;
        }

        $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
        $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
        $fromName = (string) ($brand.' · команда сервиса');
        $appUrl = rtrim((string) config('app.url'), '/');
        $subscriptionUrl = $appUrl.'/sub/preview-trial-followup';
        $paymentUrl = url(route('cabinet.payment', [], false));

        Mail::to($to)->send(new TrialFollowupMail(
            brand: $brand,
            supportFromAddress: $fromAddress,
            supportFromName: $fromName,
            subscriptionUrl: $subscriptionUrl,
            paymentUrl: $paymentUrl,
            appUrl: $appUrl,
        ));

        $this->info("Отправлено: {$to}");

        return self::SUCCESS;
    }
}
