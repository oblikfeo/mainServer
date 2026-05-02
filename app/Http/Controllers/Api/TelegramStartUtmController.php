<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramStartUtmLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Deep link вида ?start=utm_… — фиксация на бэкенде (ТЗ п.2).
 */
final class TelegramStartUtmController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'utm_param' => ['required', 'string', 'max:255'],
        ]);

        TelegramStartUtmLog::query()->create([
            'telegram_user_id' => (int) $data['telegram_user_id'],
            'utm_param' => $data['utm_param'],
        ]);

        return response()->json(['ok' => true]);
    }
}
