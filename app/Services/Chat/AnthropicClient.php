<?php

namespace App\Services\Chat;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Минимальный клиент Anthropic Messages API (raw HTTP через Laravel HTTP client).
 * Стриминг SSE; опциональный исходящий прокси (ANTHROPIC_PROXY) — чтобы запросы
 * уходили через зарубежный egress, а не с IP хаба.
 */
final class AnthropicClient
{
    public function isConfigured(): bool
    {
        return trim((string) config('chat.api_key')) !== '';
    }

    /**
     * Запускает стриминговый запрос к /v1/messages и возвращает ответ
     * с нечитанным телом (SSE-поток Anthropic).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function streamMessage(array $messages): Response
    {
        $apiKey = trim((string) config('chat.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('Chat: не задан ANTHROPIC_API_KEY');
        }

        $options = ['stream' => true];
        $proxy = trim((string) config('chat.proxy'));
        if ($proxy !== '') {
            $options['proxy'] = $proxy;
        }

        return Http::baseUrl((string) config('chat.base_url'))
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->withOptions($options)
            ->connectTimeout((int) config('chat.connect_timeout'))
            ->timeout((int) config('chat.request_timeout'))
            ->post('/v1/messages', [
                'model' => (string) config('chat.model'),
                'max_tokens' => (int) config('chat.max_tokens'),
                'stream' => true,
                // Чат должен отвечать быстро: расширенное размышление выключено.
                'thinking' => ['type' => 'disabled'],
                'system' => [
                    [
                        'type' => 'text',
                        'text' => (string) config('chat.system_prompt'),
                        'cache_control' => ['type' => 'ephemeral'],
                    ],
                ],
                'messages' => $messages,
            ]);
    }
}
