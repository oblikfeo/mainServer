<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBotRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramBotRegisterController extends Controller
{
    public function store(Request $request, TelegramBotRegistrationService $registration): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'telegram_chat_id' => ['required', 'integer'],
            'telegram_username' => ['nullable', 'string', 'max:255'],
            'telegram_first_name' => ['nullable', 'string', 'max:255'],
            'referral_param' => ['nullable', 'string', 'max:255'],
            'offer_accepted' => ['accepted'],
        ], [
            'offer_accepted.accepted' => 'Нужно согласие с публичной офертой.',
        ]);

        try {
            $result = $registration->registerOrGet(
                (int) $data['telegram_user_id'],
                $data['telegram_username'] ?? null,
                $data['telegram_first_name'] ?? null,
                $data['referral_param'] ?? null,
            );
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'telegram_id_taken') {
                return response()->json([
                    'ok' => false,
                    'error' => 'telegram_id_taken',
                    'message' => 'Этот Telegram уже привязан к другому аккаунту.',
                ], 409);
            }

            throw $e;
        }

        $created = $result['created'];

        return response()->json([
            'ok' => true,
            'created' => $created,
            'message_for_chat' => $created
                ? TelegramBotRegistrationService::WELCOME_REGISTERED
                : 'Вы уже зарегистрированы. Пользуйтесь меню ниже.',
        ]);
    }
}
