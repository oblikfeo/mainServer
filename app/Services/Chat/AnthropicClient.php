<?php

namespace App\Services\Chat;

use RuntimeException;

/**
 * Минимальный клиент Anthropic Messages API на ext-curl.
 *
 * Нарочно не Laravel HTTP client: Guzzle при stream=true использует
 * StreamHandler, который не поддерживает SOCKS-прокси, а исходящие запросы
 * к API должны уходить через WG-туннель на cdn-egress (ANTHROPIC_PROXY,
 * socks5h://…), чтобы выходить не с российского IP. cURL умеет и socks5h,
 * и инкрементальную отдачу через CURLOPT_WRITEFUNCTION.
 */
final class AnthropicClient
{
    public function isConfigured(): bool
    {
        return trim((string) config('chat.api_key')) !== '';
    }

    /**
     * Стримит SSE-ответ /v1/messages в callback по мере поступления.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): bool  $onChunk  получает куски SSE; false — прервать передачу
     * @return array{ok: bool, status: int, error: string, aborted: bool}
     */
    public function streamMessage(array $messages, callable $onChunk): array
    {
        $apiKey = trim((string) config('chat.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('Chat: не задан ANTHROPIC_API_KEY');
        }

        $payload = [
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
        ];

        $status = 0;
        $errorBody = '';
        $aborted = false;

        $ch = curl_init(rtrim((string) config('chat.base_url'), '/').'/v1/messages');

        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'x-api-key: '.$apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
                'accept: text/event-stream',
            ],
            CURLOPT_CONNECTTIMEOUT => (int) config('chat.connect_timeout'),
            CURLOPT_TIMEOUT => (int) config('chat.request_timeout'),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, string $data) use (&$status, &$errorBody, &$aborted, $onChunk): int {
                if ($status === 0) {
                    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                }
                if ($status === 200) {
                    if ($onChunk($data) === false) {
                        $aborted = true;

                        return 0; // клиент отключился — обрываем передачу
                    }

                    return strlen($data);
                }
                $errorBody .= $data;

                return strlen($data);
            },
        ];

        $proxy = trim((string) config('chat.proxy'));
        if ($proxy !== '') {
            $options[CURLOPT_PROXY] = $proxy;
        }

        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($aborted) {
            return ['ok' => false, 'status' => $status, 'error' => 'client aborted', 'aborted' => true];
        }

        if ($errno !== 0) {
            return ['ok' => false, 'status' => $status, 'error' => "curl #{$errno}: {$curlError}", 'aborted' => false];
        }

        if ($status !== 200) {
            return ['ok' => false, 'status' => $status, 'error' => mb_substr($errorBody, 0, 2000), 'aborted' => false];
        }

        return ['ok' => true, 'status' => 200, 'error' => '', 'aborted' => false];
    }
}
