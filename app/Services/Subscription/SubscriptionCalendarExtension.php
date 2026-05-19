<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Services\Xui\XuiSubscriptionExpirySync;

/**
 * Продлевает срок подписки в БД и на панелях 3x-ui.
 */
final class SubscriptionCalendarExtension
{
    public function __construct(
        private readonly XuiSubscriptionExpirySync $xuiExpiry,
    ) {}

    /**
     * @param  float|int  $days  Календарные сутки (дробь допустима, интерпретируется как дни * 86400 c).
     */
    public function addCalendarDays(Subscription $subscription, float|int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $addMs = (int) round(((float) $days) * 86_400_000);
        if ($addMs < 1) {
            return;
        }

        $row = Subscription::query()->whereKey($subscription->id)->lockForUpdate()->first();
        if ($row === null) {
            return;
        }

        $nowMs = (int) (now()->getTimestamp() * 1000);
        $ms = (int) $row->expiry_ms;

        if ($ms <= 0) {
            $newMs = $nowMs + $addMs;
        } else {
            $base = max($ms, $nowMs);
            $newMs = $base + $addMs;
        }

        $row->expiry_ms = $newMs;
        $row->save();

        $this->xuiExpiry->syncForSubscription($row);
    }
}
