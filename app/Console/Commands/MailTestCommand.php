<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {to : Email получателя} {--subject=Test email : Тема письма}';

    protected $description = 'Отправить тестовое письмо через текущий MAIL_MAILER (например Resend).';

    public function handle(): int
    {
        $to = trim((string) $this->argument('to'));
        $subject = (string) $this->option('subject');

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Укажите корректный email получателя.');

            return self::FAILURE;
        }

        Mail::raw(
            "Тестовое письмо из приложения: ".config('app.name')."\n".
            'URL: '.config('app.url')."\n".
            'Mailer: '.config('mail.default')."\n",
            function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            }
        );

        $this->info("Отправлено: {$to}");

        return self::SUCCESS;
    }
}

