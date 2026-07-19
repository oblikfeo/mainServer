<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatStreamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ИИ-ассистент внутри Telegram-бота (кнопка «Поддержка»).
 *
 * Бот присылает историю диалога, Laravel прогоняет её через тот же ChatStreamer
 * и системный промпт, что и веб-чат /chat, и возвращает цельный текст ответа.
 * Ключи API и прокси остаются на стороне Laravel — бот к Anthropic не ходит.
 *
 * Если модель решает, что нужен живой оператор, она ставит в конце ответа
 * служебный маркер [[HANDOFF]] — контроллер вырезает его из текста и возвращает
 * handoff=true, а бот дальше переключает клиента на группу поддержки.
 */
final class TelegramBotChatController extends Controller
{
    /** Маркер перевода диалога на живого оператора (клиенту не показывается). */
    private const HANDOFF_MARKER = '[[HANDOFF]]';

    public function __construct(private readonly ChatStreamer $streamer)
    {
    }

    public function reply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $messages = $this->normalizeHistory($validated['messages']);
        if ($messages === []) {
            return response()->json(['ok' => false, 'error' => 'empty'], 422);
        }

        $modelKey = (string) config('chat.default_model');
        $handoffPrompt = (string) config('chat.telegram_handoff_prompt', '');

        $answer = '';
        try {
            $result = $this->streamer->stream($modelKey, $messages, function (string $text) use (&$answer): bool {
                $answer .= $text;

                return true;
            }, $handoffPrompt);
        } catch (Throwable $e) {
            Log::error('TelegramBotChat: запрос к API не удался', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => 'api_failed'], 502);
        }

        if (! $result['ok']) {
            Log::error('TelegramBotChat: API вернул ошибку', [
                'status' => $result['status'],
                'error' => $result['error'],
            ]);

            return response()->json(['ok' => false, 'error' => 'api_error', 'status' => $result['status']], 502);
        }

        $handoff = false;
        $pos = strpos($answer, self::HANDOFF_MARKER);
        if ($pos !== false) {
            $handoff = true;
            $answer = rtrim(substr($answer, 0, $pos));
        }

        $answer = trim($answer);
        if ($answer === '') {
            // На всякий случай: если модель прислала только маркер без текста.
            $answer = $handoff
                ? 'Передаю ваш вопрос живому человеку — оператор скоро с вами свяжется.'
                : 'Не удалось сформировать ответ. Попробуйте переформулировать вопрос.';
        }

        return response()->json([
            'ok' => true,
            'reply' => $answer,
            'handoff' => $handoff,
        ]);
    }

    /**
     * Сжимает диалог клиента с ассистентом в 1-2 предложения «суть проблемы»
     * для карточки оператора в группе поддержки. Отдельный вызов, чтобы не
     * пересылать оператору всю переписку.
     */
    public function summarize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $messages = $this->normalizeHistory($validated['messages']);
        if ($messages === []) {
            return response()->json(['ok' => false, 'error' => 'empty'], 422);
        }

        // Диалог сворачиваем в один текстовый блок и просим модель дать суть.
        $transcript = [];
        foreach ($messages as $m) {
            $who = $m['role'] === 'user' ? 'Клиент' : 'Ассистент';
            $transcript[] = "{$who}: {$m['content']}";
        }
        $prompt = "Ниже переписка клиента с ассистентом поддержки. Сформулируй для оператора суть обращения клиента в 1-2 предложениях: в чём проблема или запрос и что уже пробовали. Только суть, без вступлений, без обращения к оператору, обычным текстом.\n\n".implode("\n", $transcript);

        $modelKey = (string) config('chat.default_model');
        $summarySuffix = (string) config('chat.telegram_summary_prompt', '');

        $summary = '';
        try {
            $result = $this->streamer->stream(
                $modelKey,
                [['role' => 'user', 'content' => $prompt]],
                function (string $text) use (&$summary): bool {
                    $summary .= $text;

                    return true;
                },
                $summarySuffix,
            );
        } catch (Throwable $e) {
            Log::error('TelegramBotChat: саммари не удалось', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => 'api_failed'], 502);
        }

        $summary = trim($summary);
        if (! $result['ok'] || $summary === '') {
            return response()->json(['ok' => false, 'error' => 'api_error'], 502);
        }

        return response()->json(['ok' => true, 'summary' => $summary]);
    }

    /**
     * Обрезает историю до лимитов и приводит к формату API (как в ChatController).
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

        return $normalized;
    }
}
