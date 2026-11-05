<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Promo\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Окно «Есть промокод?» после регистрации в Telegram-боте. Коды те же, что на /promo (config/promo.php);
 * условие тоже: код вводится только при регистрации нового аккаунта.
 */
final class TelegramBotPromoController extends Controller
{
    public function redeem(Request $request, PromoCodeService $promo): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:64'],
        ]);

        $user = User::query()->where('telegram_id', (int) $data['telegram_user_id'])->first();
        if ($user === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_linked',
                'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
            ], 404);
        }

        $blocked = $promo->blockedReason($user);
        if ($blocked !== null) {
            return self::blockedResponse($blocked);
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
            return self::blockedResponse((string) $promo->blockedReason($user));
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

    private static function blockedResponse(string $reason): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => $reason,
            'message' => $reason === 'already_used'
                ? 'Промокод уже активирован. На один аккаунт действует один промокод.'
                : 'Промокод вводится только при регистрации нового аккаунта.',
        ], 422);
    }
}
