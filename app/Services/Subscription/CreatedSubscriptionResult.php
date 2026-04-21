<?php

namespace App\Services\Subscription;

use App\Models\Subscription;

final class CreatedSubscriptionResult
{
    public function __construct(
        public Subscription $subscription,
        public string $subscriptionUrl,
        public string $wifiVlessLine,
        public string $wifi2VlessLine,
        public string $fiVlessLine,
        public string $nlVlessLine,
        public ?string $decodeWarning,
    ) {}
}
