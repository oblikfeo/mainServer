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
     * @return array{trial_days?: int, welcome_popup?: bool}|null
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
        $this->issuer->issueFromPromo($user, $days, $code);

        $user->forceFill([
            'registration_promo_code' => $code,
            'promo_welcome_pending' => (bool) ($def['welcome_popup'] ?? false),
        ])->save();
    }
}
