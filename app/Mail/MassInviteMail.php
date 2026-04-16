<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MassInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $loginEmail,
        public readonly string $brand,
        public readonly string $supportFromAddress,
        public readonly string $supportFromName,
        public readonly string $forgotPasswordUrl,
        public readonly string $appUrl,
        public readonly string $supportEmail,
        public readonly bool $isPreview = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isPreview
            ? $this->brand.' — тест: вход в кабинет'
            : $this->brand.' — вход в личный кабинет';

        return new Envelope(
            subject: $subject,
            from: new \Illuminate\Mail\Mailables\Address($this->supportFromAddress, $this->supportFromName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mass-invite',
            with: [
                'loginEmail' => $this->loginEmail,
                'brand' => $this->brand,
                'forgotPasswordUrl' => $this->forgotPasswordUrl,
                'appUrl' => $this->appUrl,
                'supportEmail' => $this->supportEmail,
                'isPreview' => $this->isPreview,
            ],
        );
    }
}
