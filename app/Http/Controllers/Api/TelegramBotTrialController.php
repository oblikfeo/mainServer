<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestKey;
use App\Models\User;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Xui\XuiPanelException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Выдача пробной подписки из Telegram-бота. Условия те же, что в ЛК
 * (CabinetTestKeysController): подтверждённая личность (почта ИЛИ привязанный
 * Telegram — для бота выполнено всегда), нет активной платной подписки,
 * триал ещё не использован.
 */
final class TelegramBotTrialController extends Controller
{
    private const NOT_LINKED = [
        'ok' => false,
        'error' => 'not_linked',
        'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
    ];

    public function issue(Request $request, TrialSubscriptionIssuer $issuer): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('telegram_id', (int) $data['telegram_user_id'])->first();
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        if (! $user->hasVerifiedIdentity()) {
            return response()->json([
                'ok' => false,
                'error' => 'identity_not_verified',
                'message' => 'Чтобы получить тестовую подписку, подтвердите почту в профиле или привяжите Telegram.',
            ], 422);
        }

        if ($user->hasActiveNonTrialSubscription()) {
            return response()->json([
                'ok' => false,
                'error' => 'has_paid_subscription',
                'message' => 'Тестовая подписка недоступна: у вас уже есть активная платная подписка.',
            ], 422);
        }

        $activeTrial = $user->activeTrialSubscription();
        if ($activeTrial !== null) {
            $until = $activeTrial->expiresAt()
                ?->timezone((string) config('app.timezone'))
                ->format('d.m.Y H:i');

            return response()->json([
                'ok' => false,
                'error' => 'trial_already_active',
                'message' => $until !== null
                    ? 'Тестовая подписка уже активна — до '.$until.'. Подключение — в Личном кабинете.'
                    : 'Тестовая подписка уже активна. Подключение — в Личном кабинете.',
            ], 422);
        }

        $existingKey = TestKey::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();
        if ($existingKey) {
            return response()->json([
                'ok' => false,
                'error' => 'trial_already_active',
                'message' => 'Тестовый доступ уже активен. Подключение — в Личном кабинете.',
            ], 422);
        }

        if (! $user->canSelfIssueCabinetTrial()) {
            return response()->json([
                'ok' => false,
                'error' => 'trial_already_used',
                'message' => 'Пробный период уже использован. Оформите платную подписку — кнопка «Оплатить» в меню.',
            ], 422);
        }

        $referralSlot = (int) $user->referral_invitee_test_issues_remaining > 0;

        try {
            if ($referralSlot) {
                $result = $issuer->issueFromCabinet($user, true);
                $user->forceFill([
                    'referral_invitee_test_issues_remaining' => max(
                        0,
                        (int) $user->referral_invitee_test_issues_remaining - 1
                    ),
                ])->save();
            } else {
                $result = $issuer->issueFromCabinet($user, false);
            }
        } catch (XuiPanelException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'issue_failed',
                'message' => 'Не удалось выдать тестовую подписку. Попробуйте позже или напишите в поддержку.',
            ], 502);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'issue_failed',
                'message' => 'Не удалось выдать тестовую подписку. Попробуйте позже или напишите в поддержку.',
            ], 502);
        }

        $expiresAt = $result->subscription->expiresAt()
            ?->timezone((string) config('app.timezone'))
            ->format('d.m.Y H:i');

        return response()->json([
            'ok' => true,
            'expires_at' => $expiresAt,
            'devices' => (int) $result->subscription->devices,
            'quota_gb' => max(1, (int) config('trial_subscription.quota_gb', 5)),
        ]);
    }
}
