<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (mixed $notifiable, #[\SensitiveParameter] string $token): MailMessage {
            $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
            $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
            $fromName = (string) ($brand.' · поддержка');

            $broker = (string) config('auth.defaults.passwords');
            $expireMinutes = (int) config("auth.passwords.{$broker}.expire", 60);

            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->from($fromAddress, $fromName)
                ->subject($brand.' — восстановление пароля')
                ->view('emails.reset-password', [
                    'brand' => $brand,
                    'resetUrl' => $resetUrl,
                    'supportEmail' => $fromAddress,
                    'appUrl' => rtrim((string) config('app.url'), '/'),
                    'expireMinutes' => $expireMinutes,
                ]);
        });
    }
}
