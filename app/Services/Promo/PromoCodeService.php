<?php

namespace App\Services\Promo;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\CreatedSubscriptionResult;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Xui\XuiPanelException;
use Illuminate\Support\Facades\DB;

final class PromoCodeService
{
    public function __construct(
        private readonly TrialSubscriptionIssuer $issuer,
    ) {}

    public function normalize(?string $raw): string
    {
        // Русские буквы, похожие на латинские (ОСТ31 на русской раскладке → OCT31).
        return strtr(mb_strtoupper(trim((string) $raw), 'UTF-8'), [
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H',
            'О' => 'O', 'Р' => 'P', 'С' => 'C', 'Т' => 'T', 'У' => 'Y', 'Х' => 'X',
        ]);
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

        $this->apply($user, $code, $def, (bool) ($def['welcome_popup'] ?? false));
    }

    /**
     * Почему код уже нельзя применить: already_used, not_new или null, если можно.
     * Как и на сайте, код вводится только при регистрации: у аккаунта ещё не было ни подписки, ни пробного, ни оплаты.
     */
    public function blockedReason(User $user): ?string
    {
        if (filled($user->registration_promo_code)) {
            return 'already_used';
        }

        $id = (int) $user->id;
        $used = Subscription::query()->where('user_id', $id)->orWhere('archived_from_user_id', $id)->exists()
            || $user->testKeys()->exists()
            || $user->paymentOrders()->where('status', 'paid')->exists();

        return $used ? 'not_new' : null;
    }

    /**
     * Ввод кода в окне после регистрации в Telegram-боте. Условия показывает сам бот, поэтому окно в кабинете не включаем.
     * Строка аккаунта блокируется, чтобы двойная отправка не выдала код дважды.
     *
     * @return CreatedSubscriptionResult|null null — код уже нельзя применить (проверить blockedReason)
     *
     * @throws XuiPanelException
     */
    public function redeemForAccount(User $user, string $code): ?CreatedSubscriptionResult
    {
        $def = $this->definition($code);
        if ($def === null) {
            return null;
        }

        return DB::transaction(function () use ($user, $code, $def): ?CreatedSubscriptionResult {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($this->blockedReason($locked) !== null) {
                return null;
            }

            return $this->apply($locked, $code, $def, false);
        });
    }

    /**
     * @param  array{trial_days?: int, devices?: int, first_payment_bonus_days?: int, welcome_popup?: bool}  $def
     *
     * @throws XuiPanelException
     */
    private function apply(User $user, string $code, array $def, bool $welcomePopup): CreatedSubscriptionResult
    {
        $days = max(1, (int) ($def['trial_days'] ?? 0));
        $devices = isset($def['devices']) ? max(1, (int) $def['devices']) : null;
        $result = $this->issuer->issueFromPromo($user, $days, $code, $devices);

        $user->forceFill([
            'registration_promo_code' => $code,
            'promo_welcome_pending' => $welcomePopup,
            'promo_bonus_days' => max(0, (int) ($def['first_payment_bonus_days'] ?? 0)),
        ])->save();

        return $result;
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
