<?php

namespace App\Services\Subscription;

/**
 * Результат проверки HWID перед выдачей /sub/{token}.
 */
enum SubscriptionFeedHwidVerdict
{
    case Allowed;
    case MissingHwid;
    case DeviceLimitExceeded;
}
