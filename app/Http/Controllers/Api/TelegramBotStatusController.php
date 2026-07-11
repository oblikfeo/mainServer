<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramBotStatusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('telegram_id', (int) $data['telegram_user_id'])->first();

        if ($user === null) {
            return response()->json([
                'ok' => true,
                'linked' => false,
            ]);
        }

        return response()->json([
            'ok' => true,
            'linked' => true,
            'user_id' => (int) $user->id,
        ]);
    }
}
