<?php

namespace App\Http\Controllers;

use App\Services\Chat\ChatStreamer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatController extends Controller
{
    public function __construct(private readonly ChatStreamer $streamer)
    {
    }

    public function show()
    {
        return view('chat');
    }

    /**
     * Принимает историю диалога и стримит ответ модели клиенту единым SSE:
     * event delta {text}, event chat_error {message}, event done.
     */
    public function message(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'model' => ['nullable', 'string', Rule::in(array_keys((array) config('chat.models')))],
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $modelKey = $validated['model'] ?? (string) config('chat.default_model');
        $messages = $this->normalizeHistory($validated['messages']);

        return response()->stream(function () use ($modelKey, $messages): void {
            @set_time_limit(0);
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $emit = function (string $event, array $data): void {
                echo "event: {$event}\ndata: ".json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
                flush();
            };

            try {
                $result = $this->streamer->stream($modelKey, $messages, function (string $text) use ($emit): bool {
                    $emit('delta', ['text' => $text]);

                    return ! connection_aborted();
                });
            } catch (Throwable $e) {
                Log::error('Chat: не удалось выполнить запрос к API', ['model' => $modelKey, 'error' => $e->getMessage()]);
                $emit('chat_error', ['message' => 'Не удалось связаться с сервисом. Попробуйте ещё раз.']);

                return;
            }

            if ($result['ok']) {
                $emit('done', []);

                return;
            }

            if ($result['aborted']) {
                return;
            }

            Log::error('Chat: API вернул ошибку', [
                'model' => $modelKey,
                'status' => $result['status'],
                'error' => $result['error'],
            ]);

            $emit('chat_error', ['message' => match (true) {
                $result['error'] === 'not_configured' => 'Эта модель пока не настроена.',
                $result['status'] === 429 => 'Слишком много запросов, подождите немного.',
                default => 'Сервис вернул ошибку. Попробуйте ещё раз.',
            }]);
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
