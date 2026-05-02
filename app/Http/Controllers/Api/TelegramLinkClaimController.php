<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramLinkSession;
use App\Models\User;
use App\Services\Telegram\TelegramAccountLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramLinkClaimController extends Controller
{
    private const WELCOME_LINKED = 'Добро пожаловать в сервис "Надежда". Ваш аккаунт успешно привязан. Теперь мы на связи: здесь будут появляться сервисные уведомления о вашей подписке и начислениях бонусов. Никакого спама, только по делу.';

    public function claim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'deeplink_token' => ['required', 'string', 'min:32', 'max:128'],
            'telegram_user_id' => ['required', 'integer'],
            'telegram_chat_id' => ['required', 'integer'],
            'telegram_username' => ['nullable', 'string', 'max:255'],
        ]);

        $hash = TelegramAccountLinkService::hashDeepLinkToken($data['deeplink_token']);

        /** @var TelegramLinkSession|null $session */
        $session = TelegramLinkSession::query()->where('token_hash', $hash)->first();

        if ($session === null) {
            return response()->json([
                'ok' => false,
                'error' => 'session_not_found',
                'message' => 'Привязочная ссылка недействительна: на сервере нет активного запроса. В профиле на сайте снова нажмите «Привязать Telegram» и перейдите по новой ссылке без задержки. Обычно это случается, если открыт старый диплинк или ссылку из прошлого нажатия.',
            ], 422);
        }

        if ($session->isExpired()) {
            return response()->json([
                'ok' => false,
                'error' => 'session_expired',
                'message' => 'Время действия привязочной ссылки истекло. В профиле на сайте запросите новую («Привязать Telegram») и откройте её в Telegram в течение нескольких минут.',
            ], 422);
        }

        /** @var User|null $owner */
        $owner = User::query()->find($session->user_id);
        if ($owner === null) {
            return response()->json(['ok' => false, 'error' => 'user_missing'], 422);
        }

        if ($owner->telegram_id !== null) {
            return response()->json([
                'ok' => false,
                'error' => 'already_linked',
                'message' => 'Этот аккаунт уже привязан к Telegram.',
            ], 422);
        }

        $tgId = (int) $data['telegram_user_id'];

        if ($session->telegram_user_id !== null && (int) $session->telegram_user_id !== $tgId) {
            return response()->json([
                'ok' => false,
                'error' => 'token_claimed_by_other',
                'message' => 'Эта ссылка уже использована другим аккаунтом Telegram. Запросите новую ссылку на сайте.',
            ], 409);
        }

        if (TelegramAccountLinkService::telegramIdTakenByAnotherUser($tgId, $owner->id)) {
            return response()->json([
                'ok' => false,
                'error' => 'telegram_already_linked',
                'message' => 'Этот Telegram уже привязан к другому пользователю.',
            ], 409);
        }

        $owner->forceFill([
            'telegram_id' => $tgId,
            'telegram_username' => $data['telegram_username'],
            'telegram_linked_at' => now(),
            'telegram_bot_blocked_at' => null,
        ])->save();

        TelegramLinkSession::query()->where('user_id', $owner->id)->delete();

        return response()->json([
            'ok' => true,
            'message_for_chat' => self::WELCOME_LINKED,
        ]);
    }
}
