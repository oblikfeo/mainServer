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

        if ($session === null || $session->isExpired()) {
            return response()->json([
                'ok' => false,
                'error' => 'invalid_or_expired_token',
                'message' => 'Сессия не найдена или истекла. Откройте раздел профиля на сайте и запросите новую ссылку.',
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

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = TelegramAccountLinkService::hashOtpCode($code);

        $session->forceFill([
            'otp_code_hash' => $otpHash,
            'telegram_user_id' => $tgId,
            'telegram_chat_id' => (int) $data['telegram_chat_id'],
            'telegram_username' => $data['telegram_username'],
        ])->save();

        return response()->json([
            'ok' => true,
            'code_plain' => $code,
            'message_for_chat' => 'Код для сайта «Надежда»: '.$code."\n\nВведите эти цифры в профиле на сайте в поле подтверждения Telegram.",
        ]);
    }
}
