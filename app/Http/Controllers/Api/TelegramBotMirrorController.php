<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramCabinetAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramBotMirrorController extends Controller
{
    public function show(Request $request, TelegramCabinetAccessService $cabinetAccess): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        $url = $cabinetAccess->loginUrlForTelegramUserId((int) $data['telegram_user_id']);
        if ($url === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_linked',
                'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'url' => $url,
        ]);
    }
}
