<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Services\Xui\XuiSubscriptionLimitIpSync;
use App\Services\Xui\XuiSubscriptionQuotaSync;
use Illuminate\Support\Facades\DB;

/**
 * Платное продление: +дни календаря, +ГБ квоты (если квота не «бесконечная»), +устройства.
 */
final class ApplySubscriptionRenewalPack
{
    public function __construct(
        private readonly SubscriptionCalendarExtension $calendar,
        private readonly XuiSubscriptionQuotaSync $quotaSync,
        private readonly XuiSubscriptionLimitIpSync $limitIpSync,
    ) {}

    public function apply(
        int $subscriptionId,
        int $addDays,
        int $addQuotaGb,
        int $addDevices,
        ?string $tariffPlan = null,
    ): Subscription {
        if ($addDays < 1 && $addQuotaGb < 1 && $addDevices < 1) {
            throw new \InvalidArgumentException('Пустой пакет продления');
        }

        return DB::transaction(function () use ($subscriptionId, $addDays, $addQuotaGb, $addDevices, $tariffPlan): Subscription {
            /** @var Subscription|null $locked */
            $locked = Subscription::query()->whereKey($subscriptionId)->lockForUpdate()->first();
            if ($locked === null) {
                throw new \RuntimeException('Подписка не найдена');
            }

            if ($addDays > 0) {
                $this->calendar->addCalendarDays($locked, $addDays);
                $locked->refresh();
            }

            if ($addQuotaGb > 0) {
                if ((int) $locked->quota_gb > 0) {
                    $locked->quota_gb = (int) $locked->quota_gb + $addQuotaGb;
                    $locked->save();
                }
            }

            if ($addDevices > 0) {
                $locked->devices = min(100, (int) $locked->devices + $addDevices);
                $locked->save();
            }

            if ($locked->is_trial) {
                $locked->is_trial = false;
                $planDevices = $this->planDeviceFloor($tariffPlan);
                if ($planDevices > 0) {
                    $locked->devices = max((int) $locked->devices, $planDevices);
                }
                $locked->save();
            }

            $this->quotaSync->syncForSubscription($locked);
            $this->limitIpSync->syncForSubscription($locked);

            return $locked->fresh();
        });
    }

    private function planDeviceFloor(?string $tariffPlan): int
    {
        if ($tariffPlan === null || $tariffPlan === '' || $tariffPlan === 'bonus') {
            return 0;
        }

        $products = config('payments.products', []);

        return max(0, (int) (is_array($products[$tariffPlan] ?? null) ? ($products[$tariffPlan]['devices'] ?? 0) : 0));
    }
}
