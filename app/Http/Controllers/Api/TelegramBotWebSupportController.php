<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Support\SupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ответ оператора из группы поддержки → виджет на сайте.
 * Бот вызывает, когда оператор сделал reply на сообщение, которое не относится к Telegram-клиентам.
 */
final class TelegramBotWebSupportController extends Controller
{
    public function reply(Request $request, SupportChatService $support): JsonResponse
    {
        $data = $request->validate([
            'reply_to_message_id' => ['required', 'integer', 'min:1'],
            'text' => ['required', 'string', 'max:4096'],
            'operator_name' => ['nullable', 'string', 'max:64'],
        ]);

        $chat = $support->operatorReply((int) $data['reply_to_message_id'], trim($data['text']), $data['operator_name'] ?? null);

        return response()->json(['ok' => true, 'matched' => $chat !== null, 'chat' => $chat?->id]);
    }
}
