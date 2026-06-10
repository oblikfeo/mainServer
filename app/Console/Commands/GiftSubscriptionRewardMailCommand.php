<?php

namespace App\Console\Commands;

use App\Mail\GiftSubscriptionRewardMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GiftSubscriptionRewardMailCommand extends Command
{
    protected $signature = 'gift-reward:send-mail
                            {--to= : Email получателя (обязательно)}
                            {--url= : Ссылка подписки (обязательно)}';

    protected $description = 'Отправить письмо с подарочной подпиской (награда за находку уязвимости).';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));
        $subscriptionUrl = trim((string) $this->option('url'));

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Укажите корректный --to=email.');

            return self::FAILURE;
        }

        if ($subscriptionUrl === '' || ! filter_var($subscriptionUrl, FILTER_VALIDATE_URL)) {
            $this->error('Укажите корректный --url= со ссылкой подписки.');

            return self::FAILURE;
        }

        $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
        $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
        $fromName = (string) ($brand.' · команда сервиса');
        $appUrl = rtrim((string) config('app.url'), '/');
        $referralUrl = url(route('cabinet.referral', [], false));

        Mail::to($to)->send(new GiftSubscriptionRewardMail(
            brand: $brand,
            supportFromAddress: $fromAddress,
            supportFromName: $fromName,
            subscriptionUrl: $subscriptionUrl,
            referralUrl: $referralUrl,
            appUrl: $appUrl,
        ));

        $this->info("Отправлено: {$to}");

        return self::SUCCESS;
    }
}
