<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuickBuySubscriptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $brand,
        public readonly string $supportFromAddress,
        public readonly string $supportFromName,
        public readonly string $subscriptionUrl,
        public readonly string $cabinetLoginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->brand.' — ваша подписка',
            from: new \Illuminate\Mail\Mailables\Address($this->supportFromAddress, $this->supportFromName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quick-buy-subscription',
            with: [
                'brand' => $this->brand,
                'subscriptionUrl' => $this->subscriptionUrl,
                'cabinetLoginUrl' => $this->cabinetLoginUrl,
                'supportEmail' => $this->supportFromAddress,
                'appUrl' => rtrim((string) config('app.url'), '/'),
            ],
        );
    }
}
