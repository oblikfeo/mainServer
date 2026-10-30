<?php

namespace App\Http\Controllers;

use App\Models\SupportChat;
use App\Services\Chat\ChatStreamer;
use App\Services\Support\SupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Виджет поддержки на главной: ассистент отвечает потоком (SSE), при необходимости диалог уходит оператору
 * в Telegram, ответ оператора виджет забирает опросом /support/chat/{id}/messages.
 */
class SupportChatController extends Controller
{
    public function __construct(
        private readonly ChatStreamer $streamer,
        private readonly SupportChatService $support,
    ) {}

    /**
     * SSE: event chat {id}, затем delta {text}… и done {message_id}, либо handoff, queued, notice {text}, chat_error {message}.
     */
    public function message(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'chat' => ['nullable', 'string', 'max:48'],
            'text' => ['required', 'string', 'max:'.max(1, (int) config('chat.widget.max_message_chars', 1000))],
            'page' => ['nullable', 'string', 'max:255'],
        ]);
        $text = trim($data['text']);
        abort_if($text === '', 422);

        $chat = $this->findOrCreate($request, $data['chat'] ?? null, $data['page'] ?? null);

        return response()->stream(function () use ($request, $chat, $text): void {
            @set_time_limit(0);
            // Сбрасываем буферы, чтобы куски ответа уходили сразу (в тестах буфер нужен для чтения ответа).
            while (! app()->runningUnitTests() && ob_get_level() > 0) {
                @ob_end_flush();
            }
            $emit = function (string $event, array $payload): void {
                echo "event: {$event}\ndata: ".json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
                flush();
            };
            $emit('chat', ['id' => $chat->public_id]);

            if ($chat->isWithOperator()) {
                $msg = $chat->messages()->create(['role' => 'user', 'content' => $text]);
                $chat->forceFill(['last_message_at' => now()])->save();
                $this->support->forwardUserMessage($chat, $msg);
                $emit('queued', ['message_id' => $msg->id]);

                return;
            }

            $ipKey = 'support-widget:'.$request->ip();
            $aiAsked = $chat->messages()->where('role', 'user')->count();
            if (RateLimiter::tooManyAttempts($ipKey, max(1, (int) config('chat.widget.ip_limit_10min', 20)))
                || $aiAsked >= max(1, (int) config('chat.widget.chat_ai_limit', 30))) {
                $emit('notice', ['text' => 'Слишком много сообщений подряд. Нажмите «Позвать человека» — оператор ответит здесь же.']);

                return;
            }
            RateLimiter::hit($ipKey, 600);

            $msg = $chat->messages()->create(['role' => 'user', 'content' => $text]);
            $chat->forceFill(['last_message_at' => now()])->save();

            [$answer, $handoff, $error] = $this->streamAnswer($chat, $emit);

            if ($error !== null) {
                $emit('chat_error', ['message' => $error]);

                return;
            }
            $saved = $chat->messages()->create(['role' => 'assistant', 'content' => $answer]);
            if ($handoff) {
                $this->support->handoff($chat->fresh(), 'ai');
                $emit('handoff', []);
            }
            $emit('done', ['message_id' => $saved->id, 'user_message_id' => $msg->id]);
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /** История диалога (восстановление окна) и новые сообщения после after (ответы оператора). */
    public function messages(Request $request, string $chatId): JsonResponse
    {
        $chat = $this->owned($chatId);
        $after = max(0, (int) $request->query('after', 0));

        $rows = $chat->messages()->where('id', '>', $after)->whereIn('role', ['user', 'assistant', 'operator', 'system'])
            ->orderBy('id')->limit(100)->get();

        return response()->json([
            'status' => $chat->status,
            'contact' => $chat->contact,
            'has_email' => $chat->replyEmail() !== null,
            'messages' => $rows->map->toWidget()->values(),
        ]);
    }

    /** Кнопка «Позвать человека». */
    public function handoff(string $chatId): JsonResponse
    {
        $chat = $this->owned($chatId);
        if (! $chat->isWithOperator()) {
            $this->support->handoff($chat, 'manual');
        }

        return response()->json(['status' => SupportChat::STATUS_OPERATOR]);
    }

    /** Почта или Telegram для связи, если человек закроет страницу. */
    public function contact(Request $request, string $chatId): JsonResponse
    {
        $data = $request->validate(['contact' => ['required', 'string', 'min:3', 'max:191']]);
        $chat = $this->owned($chatId);
        $chat->forceFill(['contact' => trim($data['contact'])])->save();
        $this->support->contactUpdated($chat);

        return response()->json(['ok' => true]);
    }

    private function findOrCreate(Request $request, ?string $publicId, ?string $page): SupportChat
    {
        $chat = $publicId !== null && $publicId !== ''
            ? SupportChat::query()->where('public_id', $publicId)->first()
            : null;

        if ($chat === null) {
            $chat = SupportChat::query()->create([
                'public_id' => Str::random(40),
                'user_id' => $request->user()?->id,
                'status' => SupportChat::STATUS_AI,
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'page' => $page !== null ? mb_substr($page, 0, 255) : null,
            ]);
        } elseif ($chat->user_id === null && $request->user() !== null) {
            $chat->forceFill(['user_id' => $request->user()->id])->save();
        }

        return $chat;
    }

    private function owned(string $chatId): SupportChat
    {
        return SupportChat::query()->where('public_id', $chatId)->firstOrFail();
    }

    /**
     * Стрим ответа ассистента с вырезанием маркера [[HANDOFF]]: хвост, похожий на начало маркера, придерживаем.
     *
     * @return array{0: string, 1: bool, 2: ?string} [текст, нужен оператор, ошибка]
     */
    private function streamAnswer(SupportChat $chat, callable $emit): array
    {
        $marker = SupportChatService::HANDOFF_MARKER;
        $full = '';
        $sent = 0;
        $suffix = (string) config('chat.widget.prompt', '');

        $flush = function (bool $final) use (&$full, &$sent, $marker, $emit): void {
            $pos = mb_strpos($full, $marker);
            $limit = $pos !== false ? $pos : mb_strlen($full);
            if (! $final && $pos === false) {
                // Не отдаём хвост, который может оказаться началом маркера.
                for ($k = min(mb_strlen($marker) - 1, $limit); $k > 0; $k--) {
                    if (str_starts_with($marker, mb_substr($full, $limit - $k))) {
                        $limit -= $k;
                        break;
                    }
                }
            }
            if ($limit > $sent) {
                $emit('delta', ['text' => mb_substr($full, $sent, $limit - $sent)]);
                $sent = $limit;
            }
        };

        try {
            $result = $this->streamer->stream((string) config('chat.default_model'), $this->support->aiHistory($chat), function (string $t) use (&$full, $flush): bool {
                $full .= $t;
                $flush(false);

                return ! connection_aborted();
            }, $suffix);
        } catch (Throwable $e) {
            Log::error('support.widget: запрос к API не удался', ['error' => $e->getMessage()]);

            return ['', false, 'Ассистент сейчас недоступен. Нажмите «Позвать человека» — ответит оператор.'];
        }

        if (! $result['ok'] && ! $result['aborted']) {
            Log::error('support.widget: API вернул ошибку', ['status' => $result['status'], 'error' => $result['error']]);

            return ['', false, 'Ассистент сейчас недоступен. Нажмите «Позвать человека» — ответит оператор.'];
        }

        $handoff = str_contains($full, $marker);
        $flush(true);
        $clean = trim($handoff ? mb_substr($full, 0, (int) mb_strpos($full, $marker)) : $full);

        return [$clean !== '' ? $clean : 'Передаю ваш вопрос оператору — ответ появится здесь же.', $handoff, null];
    }
}
