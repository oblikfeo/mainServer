<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Services\Hy2\BlitzClient;
use App\Services\Hy2\BlitzException;
use App\Services\Xui\XuiSubscriptionExpirySync;
use Illuminate\Support\Facades\Log;

/**
 * Продлевает срок подписки в БД и на панелях (3x-ui + Hy2).
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

        $this->syncHy2Expiry($row);
    }

    private function syncHy2Expiry(Subscription $sub): void
    {
        $hy2Username = (string) ($sub->hy2_username ?? '');
        if ($hy2Username === '' || ! config('hy2.enabled')) {
            return;
        }

        $ms = (int) $sub->expiry_ms;
        if ($ms <= 0) {
            return;
        }
        $remainingDays = (int) max(1, (int) ceil(($ms / 1000 - time()) / 86_400));

        try {
            (new BlitzClient())->editUser($hy2Username, expirationDays: $remainingDays);
        } catch (BlitzException $e) {
            Log::warning('hy2.expiry_sync_failed', [
                'subscription_id' => $sub->id,
                'username' => $hy2Username,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
