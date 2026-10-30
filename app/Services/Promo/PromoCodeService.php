<?php

namespace App\Services\Promo;

use App\Models\User;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Xui\XuiPanelException;

final class PromoCodeService
{
    public function __construct(
        private readonly TrialSubscriptionIssuer $issuer,
    ) {}

    public function normalize(?string $raw): string
    {
        return strtoupper(trim((string) $raw));
    }

    /**
     * @return array{trial_days?: int, devices?: int, first_payment_bonus_days?: int, welcome_popup?: bool}|null
     */
    public function definition(string $code): ?array
    {
        if ($code === '') {
            return null;
        }

        $codes = config('promo.codes', []);
        if (! is_array($codes)) {
            return null;
        }

        foreach ($codes as $key => $def) {
            if (strtoupper((string) $key) === $code && is_array($def)) {
                return $def;
            }
        }

        return null;
    }

    /**
     * @throws XuiPanelException
     */
    public function redeemOnRegistration(User $user, string $code): void
    {
        $def = $this->definition($code);
        if ($def === null) {
            return;
        }

        $days = max(1, (int) ($def['trial_days'] ?? 0));
        $devices = isset($def['devices']) ? max(1, (int) $def['devices']) : null;
        $this->issuer->issueFromPromo($user, $days, $code, $devices);

        $user->forceFill([
            'registration_promo_code' => $code,
            'promo_welcome_pending' => (bool) ($def['welcome_popup'] ?? false),
            'promo_bonus_days' => max(0, (int) ($def['first_payment_bonus_days'] ?? 0)),
        ])->save();
    }

    /**
     * Забрать подарок к первой оплате: возвращает число дней и обнуляет его у аккаунта (ровно один раз).
     */
    public static function takeFirstPaymentBonus(User $user): int
    {
        $days = (int) User::query()->whereKey($user->id)->value('promo_bonus_days');
        if ($days < 1) {
            return 0;
        }
        $taken = User::query()->whereKey($user->id)->where('promo_bonus_days', $days)->update(['promo_bonus_days' => 0]);
        $user->promo_bonus_days = 0;

        return $taken === 1 ? $days : 0;
    }
}
