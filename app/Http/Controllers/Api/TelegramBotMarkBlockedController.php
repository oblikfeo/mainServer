<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramBotMarkBlockedController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        $tgId = (int) $data['telegram_user_id'];

        User::query()->where('telegram_id', $tgId)->update([
            'telegram_bot_blocked_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
