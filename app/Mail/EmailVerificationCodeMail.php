<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $brand,
        public readonly string $supportFromAddress,
        public readonly string $supportFromName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->brand.' — код подтверждения',
            from: new \Illuminate\Mail\Mailables\Address($this->supportFromAddress, $this->supportFromName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-code',
            with: [
                'code' => $this->code,
                'brand' => $this->brand,
                'supportEmail' => $this->supportFromAddress,
                'appUrl' => rtrim((string) config('app.url'), '/'),
            ],
        );
    }
}

