<?php

namespace App\Services\Account;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Сводка по подписке аккаунта для кабинета, бота и страницы оплаты: состояние, срок, слоты устройств.
 */
final class AccountOverview
{
    public const STATE_NONE = 'none';         // подписки ещё не было

    public const STATE_INACTIVE = 'inactive'; // была, истекла

    public const STATE_TRIAL = 'trial';       // идёт пробный период

    public const STATE_ACTIVE = 'active';     // оплачена

    /**
     * @return array{
     *   state: string, subscription: Subscription|null, expires_at: Carbon|null, days_left: int|null,
     *   base_devices: int, unlimited_devices: bool, device_limit: int, extra_total: int, extra_active: int,
     *   max_extra: int, slots: array<int, array<string, mixed>>, waiting: array<int, array<string, mixed>>, used: int
     * }
     */
    public static function forUser(User $user): array
    {
        return self::forSubscription(AccountSubscription::forUser($user));
    }

    /** @return array<string, mixed> */
    public static function forSubscription(?Subscription $sub): array
    {
        $nowMs = DeviceSlots::nowMs();
        $tz = (string) config('app.timezone');

        if ($sub === null) {
            return [
                'state' => self::STATE_NONE, 'subscription' => null, 'expires_at' => null, 'days_left' => null,
                'base_devices' => AccountPricing::baseDevices(), 'unlimited_devices' => false,
                'device_limit' => AccountPricing::baseDevices(), 'extra_total' => 0, 'extra_active' => 0,
                'max_extra' => AccountPricing::maxExtraDevices(null), 'slots' => [], 'waiting' => [], 'used' => 0,
            ];
        }

        $active = DeviceSlots::subscriptionActive($sub, $nowMs);
        $state = ! $active ? self::STATE_INACTIVE : ($sub->is_trial ? self::STATE_TRIAL : self::STATE_ACTIVE);
        $exp = $sub->expiresAt()?->timezone($tz);
        $daysLeft = null;
        if ($exp !== null && $active) {
            $daysLeft = max(1, (int) ceil(((int) $sub->expiry_ms - $nowMs) / DeviceSlots::DAY_MS));
        }

        $base = (int) $sub->devices;
        $unlimited = $base <= 0;
        $overview = DeviceSlots::overview($sub, $nowMs);
        $limit = $sub->deviceLimit($nowMs);

        // Привязанные устройства по порядку привязки: первые $limit работают, остальные ждут оплаты.
        $devices = self::boundDevices($sub, $tz);

        $slots = [];
        for ($i = 0; $i < max(0, $base); $i++) {
            $slots[] = ['kind' => 'base', 'active' => $active, 'left_days' => null, 'until' => null, 'device' => null];
        }
        foreach ($overview as $row) {
            $slots[] = [
                'kind' => 'extra',
                'active' => $row['active'],
                'left_days' => (int) ceil($row['left_ms'] / DeviceSlots::DAY_MS),
                'until' => $row['until_ms'] !== null
                    ? Carbon::createFromTimestampMs(min($row['until_ms'], (int) $sub->expiry_ms > 0 ? (int) $sub->expiry_ms : $row['until_ms']))->timezone($tz)
                    : null,
                // Слот переживёт подписку: остаток заморозится до продления.
                'outlives_subscription' => $row['until_ms'] !== null && (int) $sub->expiry_ms > 0 && $row['until_ms'] > (int) $sub->expiry_ms,
                'device' => null,
            ];
        }

        // Устройства занимают слоты по порядку привязки (как считает лимит на ссылке подписки);
        // кому слота не хватило — ждут оплаты доп. устройства.
        $waiting = [];
        if (! $unlimited) {
            foreach (array_keys($slots) as $i) {
                $slots[$i]['device'] = $devices[$i] ?? null;
            }
            $waiting = array_slice($devices, count($slots));
        }
        if ($unlimited) {
            foreach ($devices as $d) {
                $slots[] = ['kind' => 'base', 'active' => $active, 'left_days' => null, 'until' => null, 'device' => $d];
            }
        }

        return [
            'state' => $state,
            'subscription' => $sub,
            'expires_at' => $exp,
            'days_left' => $daysLeft,
            'base_devices' => max(0, $base),
            'unlimited_devices' => $unlimited,
            'device_limit' => $limit,
            'extra_total' => $overview->count(),
            'extra_active' => $overview->where('active', true)->count(),
            'max_extra' => AccountPricing::maxExtraDevices($sub),
            'slots' => $slots,
            'waiting' => $waiting,
            'used' => count($devices),
        ];
    }

    /** @return array<int, array{hash: string, label: string, type: string, ip: string, seen_at: Carbon|null}> */
    private static function boundDevices(Subscription $sub, string $tz): array
    {
        $hashes = is_array($sub->bound_hwid_hashes) ? $sub->bound_hwid_hashes : [];
        $meta = is_array($sub->bound_hwid_meta) ? $sub->bound_hwid_meta : [];
        $out = [];
        foreach ($hashes as $hash) {
            if (! is_string($hash) || strlen($hash) !== 64) {
                continue;
            }
            $m = is_array($meta[$hash] ?? null) ? $meta[$hash] : [];
            $seen = null;
            if (! empty($m['seen_at'])) {
                try {
                    $seen = Carbon::parse((string) $m['seen_at'])->timezone($tz);
                } catch (\Throwable) {
                    $seen = null;
                }
            }
            $out[] = [
                'hash' => $hash,
                'label' => (string) ($m['label'] ?? 'Устройство'),
                'type' => (string) ($m['type'] ?? ''),
                'ip' => (string) ($m['ip'] ?? ''),
                'seen_at' => $seen,
            ];
        }

        return $out;
    }
}
