<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftSubscriptionRewardMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $brand,
        public readonly string $supportFromAddress,
        public readonly string $supportFromName,
        public readonly string $subscriptionUrl,
        public readonly string $referralUrl,
        public readonly string $appUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->brand.' — 1 месяц подписки в подарок',
            from: new Address($this->supportFromAddress, $this->supportFromName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-subscription-reward',
            with: [
                'brand' => $this->brand,
                'subscriptionUrl' => $this->subscriptionUrl,
                'referralUrl' => $this->referralUrl,
                'appUrl' => $this->appUrl,
                'supportEmail' => $this->supportFromAddress,
            ],
        );
    }
}
