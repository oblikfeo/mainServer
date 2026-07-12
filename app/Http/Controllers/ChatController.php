<?php

namespace App\Http\Controllers;

use App\Services\Chat\AnthropicClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatController extends Controller
{
    public function __construct(private readonly AnthropicClient $anthropic)
    {
    }

    public function show()
    {
        return view('chat');
    }

    /** Принимает историю диалога и проксирует SSE-поток ответа модели клиенту. */
    public function message(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $messages = $this->normalizeHistory($validated['messages']);

        return response()->stream(function () use ($messages): void {
            @set_time_limit(0);
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $emitError = function (string $text): void {
                echo "event: chat_error\ndata: ".json_encode(['message' => $text], JSON_UNESCAPED_UNICODE)."\n\n";
                flush();
            };

            if (! $this->anthropic->isConfigured()) {
                $emitError('Чат временно недоступен: сервис не настроен.');

                return;
            }

            try {
                $result = $this->anthropic->streamMessage($messages, function (string $chunk): bool {
                    echo $chunk;
                    flush();

                    return ! connection_aborted();
                });
            } catch (Throwable $e) {
                Log::error('Chat: не удалось выполнить запрос к API', ['error' => $e->getMessage()]);
                $emitError('Не удалось связаться с сервисом. Попробуйте ещё раз.');

                return;
            }

            if (! $result['ok'] && ! $result['aborted']) {
                Log::error('Chat: API вернул ошибку', [
                    'status' => $result['status'],
                    'error' => $result['error'],
                ]);
                $emitError($result['status'] === 429
                    ? 'Слишком много запросов, подождите немного.'
                    : 'Сервис вернул ошибку. Попробуйте ещё раз.');
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Обрезает историю до допустимых лимитов и приводит к формату API.
     *
     * @param  array<int, array{role: string, content: string}>  $raw
     * @return array<int, array{role: string, content: string}>
     */
    private function normalizeHistory(array $raw): array
    {
        $maxMessages = max(1, (int) config('chat.max_history_messages'));
        $maxChars = max(1, (int) config('chat.max_message_chars'));

        $messages = array_slice(array_values($raw), -$maxMessages);

        $normalized = [];
        foreach ($messages as $message) {
            $content = trim(mb_substr((string) $message['content'], 0, $maxChars));
            if ($content === '') {
                continue;
            }
            $normalized[] = [
                'role' => (string) $message['role'],
                'content' => $content,
            ];
        }

        // Первая реплика в API обязана быть от пользователя.
        while ($normalized !== [] && $normalized[0]['role'] !== 'user') {
            array_shift($normalized);
        }

        abort_if($normalized === [], 422, 'Пустое сообщение.');

        return $normalized;
    }
}
