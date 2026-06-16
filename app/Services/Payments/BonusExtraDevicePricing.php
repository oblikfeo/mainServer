<?php

namespace App\Services\Payments;

use App\Models\Subscription;

/**
 * Цена бонуса «+1 устройство»: за каждые 30 календарных дней активной подписки +100 ₽.
 */
final class BonusExtraDevicePricing
{
    /** @return array<string, mixed> */
    public function config(): array
    {
        $cfg = config('payments.bonus_extra_device', []);

        return is_array($cfg) ? $cfg : [];
    }

    public function isConfigured(): bool
    {
        return $this->addDevices() > 0 && $this->stepRub() > 0 && $this->dayBucket() > 0;
    }

    public function addDevices(): int
    {
        return max(1, (int) ($this->config()['add_devices'] ?? 1));
    }

    public function stepRub(): int
    {
        return max(1, (int) ($this->config()['amount_rub_per_30_days'] ?? 100));
    }

    public function dayBucket(): int
    {
        return max(1, (int) ($this->config()['day_bucket'] ?? 30));
    }

    /** @return int|null null — подписка без даты окончания */
    public function remainingActiveDays(Subscription $sub): ?int
    {
        $exp = $sub->expiresAt();
        if ($exp === null) {
            return null;
        }

        $tz = (string) config('app.timezone');
        $now = now()->timezone($tz)->startOfDay();
        $end = $exp->copy()->timezone($tz)->startOfDay();
        if ($end->lt($now)) {
            return 0;
        }

        return max(1, (int) $now->diffInDays($end));
    }

    public function pricingSteps(?int $remainingDays): int
    {
        if ($remainingDays === null) {
            return max(1, (int) ($this->config()['unlimited_steps'] ?? 3));
        }

        if ($remainingDays < 1) {
            return 0;
        }

        return (int) ceil($remainingDays / $this->dayBucket());
    }

    public function amountRubForSubscription(Subscription $sub): int
    {
        $remaining = $this->remainingActiveDays($sub);
        if ($remaining === 0) {
            return 0;
        }

        return $this->pricingSteps($remaining) * $this->stepRub();
    }

    public function tierRangeLabel(?int $remainingDays): string
    {
        if ($remainingDays === null) {
            return 'без ограничения срока';
        }

        $steps = $this->pricingSteps($remainingDays);
        $bucket = $this->dayBucket();

        if ($steps <= 1) {
            return '1–'.$bucket.' дн.';
        }

        $from = ($steps - 1) * $bucket + 1;
        $to = $steps * $bucket;

        return $from.'–'.$to.' дн.';
    }
}
