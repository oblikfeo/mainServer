<?php

namespace App\Services\Account;

use App\Models\DeviceSlot;
use App\Models\Subscription;
use Illuminate\Support\Collection;

/**
 * Счётчики доп. устройств. У слота есть paid_ms_left (оплачено на момент anchor_ms);
 * время списывается только пока подписка активна, при просрочке слот «замерзает».
 */
final class DeviceSlots
{
    public const DAY_MS = 86_400_000;

    public static function nowMs(): int
    {
        return (int) (now()->getTimestamp() * 1000);
    }

    public static function subscriptionActive(Subscription $sub, ?int $nowMs = null): bool
    {
        $nowMs ??= self::nowMs();
        $exp = (int) $sub->expiry_ms;

        return $exp <= 0 || $exp > $nowMs;
    }

    /** Остаток оплаченного времени слота на момент $nowMs. */
    public static function leftMs(DeviceSlot $slot, Subscription $sub, ?int $nowMs = null): int
    {
        $nowMs ??= self::nowMs();
        $exp = (int) $sub->expiry_ms;
        $tickedUntil = $exp <= 0 ? $nowMs : min($nowMs, $exp);
        $consumed = max(0, $tickedUntil - (int) $slot->anchor_ms);

        return max(0, (int) $slot->paid_ms_left - $consumed);
    }

    /** Слот даёт место под устройство: подписка активна и время на слоте есть. */
    public static function isActive(DeviceSlot $slot, Subscription $sub, ?int $nowMs = null): bool
    {
        $nowMs ??= self::nowMs();

        return self::subscriptionActive($sub, $nowMs) && self::leftMs($slot, $sub, $nowMs) > 0;
    }

    public static function activeCount(Subscription $sub, ?int $nowMs = null): int
    {
        if (! $sub->exists) {
            return 0;
        }
        $nowMs ??= self::nowMs();

        return $sub->deviceSlots->filter(fn (DeviceSlot $s) => self::isActive($s, $sub, $nowMs))->count();
    }

    /**
     * Слоты с остатком времени (активные и замороженные), по убыванию остатка.
     *
     * @return Collection<int, array{slot: DeviceSlot, left_ms: int, active: bool, until_ms: int|null}>
     */
    public static function overview(Subscription $sub, ?int $nowMs = null): Collection
    {
        $nowMs ??= self::nowMs();
        $subActive = self::subscriptionActive($sub, $nowMs);

        return $sub->deviceSlots
            ->map(function (DeviceSlot $s) use ($sub, $nowMs, $subActive): array {
                $left = self::leftMs($s, $sub, $nowMs);

                return [
                    'slot' => $s,
                    'left_ms' => $left,
                    'active' => $subActive && $left > 0,
                    // Когда слот закончится, если подписка не истечёт раньше.
                    'until_ms' => $subActive && $left > 0 ? $nowMs + $left : null,
                ];
            })
            ->filter(fn (array $r) => $r['left_ms'] > 0)
            ->sortByDesc('left_ms')
            ->values();
    }

    /**
     * Зафиксировать остатки на «сейчас». Обязательно вызывать ПЕРЕД изменением срока подписки:
     * так время простоя (подписка была неактивна) не спишется со слотов.
     */
    public static function settle(Subscription $sub, ?int $nowMs = null): void
    {
        $nowMs ??= self::nowMs();
        foreach ($sub->deviceSlots()->get() as $slot) {
            $left = self::leftMs($slot, $sub, $nowMs);
            if ($left <= 0) {
                $slot->delete();

                continue;
            }
            $slot->paid_ms_left = $left;
            $slot->anchor_ms = $nowMs;
            $slot->save();
        }
        $sub->unsetRelation('deviceSlots');
    }

    /** Продлить до $count уже существующих слотов (сначала те, у кого остаток больше). Вернёт, сколько продлено. */
    public static function extend(Subscription $sub, int $count, int $days, ?int $nowMs = null): int
    {
        if ($count < 1 || $days < 1) {
            return 0;
        }
        $nowMs ??= self::nowMs();
        $done = 0;
        foreach (self::overview($sub, $nowMs)->take($count) as $row) {
            /** @var DeviceSlot $slot */
            $slot = $row['slot'];
            $slot->paid_ms_left = $row['left_ms'] + $days * self::DAY_MS;
            $slot->anchor_ms = $nowMs;
            $slot->save();
            $done++;
        }
        $sub->unsetRelation('deviceSlots');

        return $done;
    }

    /** Создать новые слоты на $days дней (отсчёт пойдёт сразу, если подписка активна). */
    public static function add(Subscription $sub, int $count, int $days, ?int $paymentOrderId = null, ?int $nowMs = null): void
    {
        self::addMs($sub, $count, $days * self::DAY_MS, $paymentOrderId, $nowMs);
    }

    public static function addMs(Subscription $sub, int $count, int $paidMs, ?int $paymentOrderId = null, ?int $nowMs = null): void
    {
        if ($count < 1 || $paidMs < 1) {
            return;
        }
        $nowMs ??= self::nowMs();
        for ($i = 0; $i < $count; $i++) {
            DeviceSlot::query()->create([
                'subscription_id' => $sub->id,
                'paid_ms_left' => $paidMs,
                'anchor_ms' => $nowMs,
                'payment_order_id' => $paymentOrderId,
            ]);
        }
        $sub->unsetRelation('deviceSlots');
    }
}
