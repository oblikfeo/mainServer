<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Promo\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Раздел «Промокод» в Telegram-боте. Коды те же, что на /promo (config/promo.php);
 * условия тоже: один код на аккаунт и только до первой оплаты.
 */
final class TelegramBotPromoController extends Controller
{
    private const NOT_LINKED = [
        'ok' => false,
        'error' => 'not_linked',
        'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
    ];

    public function status(Request $request, PromoCodeService $promo): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        $user = User::query()->where('telegram_id', (int) $data['telegram_user_id'])->first();
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $blocked = $promo->blockedReason($user);

        return response()->json([
            'ok' => true,
            'blocked' => $blocked,
            'message' => $blocked !== null ? self::blockedMessage($user, $blocked) : null,
        ]);
    }

    public function redeem(Request $request, PromoCodeService $promo): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:64'],
        ]);

        $user = User::query()->where('telegram_id', (int) $data['telegram_user_id'])->first();
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $blocked = $promo->blockedReason($user);
        if ($blocked !== null) {
            return self::blockedResponse($user, $blocked);
        }

        $code = $promo->normalize($data['code']);
        if ($promo->definition($code) === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_found',
                'message' => 'Промокод не найден. Проверьте написание и отправьте ещё раз.',
            ], 422);
        }

        try {
            $result = $promo->redeemForAccount($user, $code);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'redeem_failed',
                'message' => 'Не удалось активировать промокод. Попробуйте позже или напишите в поддержку.',
            ], 502);
        }

        $user->refresh();
        if ($result === null) {
            // Вторая отправка, пришедшая одновременно с первой.
            return self::blockedResponse($user, (string) $promo->blockedReason($user));
        }

        return response()->json([
            'ok' => true,
            'code' => $code,
            'expires_at' => $result->subscription->expiresAt()
                ?->timezone((string) config('app.timezone'))
                ->format('d.m.Y H:i'),
            'devices' => $result->subscription->deviceLimit(),
            'bonus_days' => (int) $user->promo_bonus_days,
            'subscription_url' => $result->subscriptionUrl,
        ]);
    }

    private static function blockedResponse(User $user, string $reason): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => $reason,
            'message' => self::blockedMessage($user, $reason),
        ], 422);
    }

    private static function blockedMessage(User $user, string $reason): string
    {
        if ($reason !== 'already_used') {
            return 'Промокоды действуют только до первой оплаты подписки.';
        }

        $message = 'На вашем аккаунте уже активирован промокод '.$user->registration_promo_code.'. На один аккаунт действует один промокод.';
        $bonus = (int) $user->promo_bonus_days;
        if ($bonus > 0) {
            $message .= "\n\n🎁 ".($bonus === 30 ? '+1 месяц' : '+'.$bonus.' дн.').' в подарок ждёт вас при первой оплате подписки — начислится автоматически.';
        }

        return $message;
    }
}
