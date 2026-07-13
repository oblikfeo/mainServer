<?php

namespace App\Services\Chat;

/**
 * Стриминг ответов чата из нескольких провайдеров (Anthropic, OpenAI)
 * с нормализацией: наружу отдаётся только чистый текст через callback.
 *
 * Нарочно ext-curl, а не Laravel HTTP client: Guzzle при stream=true
 * использует StreamHandler без поддержки SOCKS-прокси, а исходящие запросы
 * должны уходить через WG-туннель на cdn-egress (ANTHROPIC_PROXY,
 * socks5h://…), чтобы выходить не с российского IP.
 */
final class ChatStreamer
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): bool  $onText  получает куски текста; false — прервать
     * @return array{ok: bool, status: int, error: string, aborted: bool}
     */
    public function stream(string $modelKey, array $messages, callable $onText): array
    {
        $cfg = config('chat.models.'.$modelKey);
        if (! is_array($cfg)) {
            return ['ok' => false, 'status' => 0, 'error' => 'unknown_model', 'aborted' => false];
        }

        return match ($cfg['provider']) {
            'anthropic' => $this->streamAnthropic($cfg, $messages, $onText),
            'openai' => $this->streamOpenAi($cfg, $messages, $onText),
            default => ['ok' => false, 'status' => 0, 'error' => 'unknown_provider', 'aborted' => false],
        };
    }

    /** @param array{model: string} $cfg */
    private function streamAnthropic(array $cfg, array $messages, callable $onText): array
    {
        $apiKey = trim((string) config('chat.api_key'));
        if ($apiKey === '') {
            return ['ok' => false, 'status' => 0, 'error' => 'not_configured', 'aborted' => false];
        }

        $payload = [
            'model' => $cfg['model'],
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

        $extract = function (string $block, ?string &$error): ?string {
            $event = null;
            $dataLines = [];
            foreach (explode("\n", $block) as $line) {
                $line = rtrim($line, "\r");
                if (str_starts_with($line, 'event:')) {
                    $event = trim(substr($line, 6));
                } elseif (str_starts_with($line, 'data:')) {
                    $dataLines[] = trim(substr($line, 5));
                }
            }
            if ($dataLines === []) {
                return null;
            }
            $data = json_decode(implode("\n", $dataLines), true);
            if (! is_array($data)) {
                return null;
            }
            if ($event === 'error' || ($data['type'] ?? '') === 'error') {
                $error = (string) ($data['error']['message'] ?? 'stream error');

                return null;
            }
            if ($event === 'content_block_delta' && ($data['delta']['type'] ?? '') === 'text_delta') {
                return (string) $data['delta']['text'];
            }

            return null;
        };

        return $this->curlSse(
            rtrim((string) config('chat.base_url'), '/').'/v1/messages',
            [
                'x-api-key: '.$apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
                'accept: text/event-stream',
            ],
            $payload,
            $extract,
            $onText,
        );
    }

    /** @param array{model: string} $cfg */
    private function streamOpenAi(array $cfg, array $messages, callable $onText): array
    {
        $apiKey = trim((string) config('chat.openai_api_key'));
        if ($apiKey === '') {
            return ['ok' => false, 'status' => 0, 'error' => 'not_configured', 'aborted' => false];
        }

        $payload = [
            'model' => $cfg['model'],
            'stream' => true,
            // gpt-5-семейство принимает только max_completion_tokens (не max_tokens)
            'max_completion_tokens' => (int) config('chat.max_tokens'),
            'messages' => array_merge(
                [['role' => 'system', 'content' => (string) config('chat.system_prompt')]],
                $messages,
            ),
        ];

        $extract = function (string $block, ?string &$error): ?string {
            $out = '';
            foreach (explode("\n", $block) as $line) {
                $line = rtrim($line, "\r");
                if (! str_starts_with($line, 'data:')) {
                    continue;
                }
                $data = trim(substr($line, 5));
                if ($data === '' || $data === '[DONE]') {
                    continue;
                }
                $decoded = json_decode($data, true);
                if (! is_array($decoded)) {
                    continue;
                }
                if (isset($decoded['error'])) {
                    $error = (string) ($decoded['error']['message'] ?? 'stream error');

                    continue;
                }
                $out .= (string) ($decoded['choices'][0]['delta']['content'] ?? '');
            }

            return $out === '' ? null : $out;
        };

        return $this->curlSse(
            rtrim((string) config('chat.openai_base_url'), '/').'/v1/chat/completions',
            [
                'Authorization: Bearer '.$apiKey,
                'content-type: application/json',
                'accept: text/event-stream',
            ],
            $payload,
            $extract,
            $onText,
        );
    }

    /**
     * Общий цикл: POST + инкрементальный разбор SSE-блоков (разделитель \n\n).
     *
     * @param  callable(string, ?string&): ?string  $extractText  блок SSE → текст (или null)
     * @param  callable(string): bool  $onText
     * @return array{ok: bool, status: int, error: string, aborted: bool}
     */
    private function curlSse(string $url, array $headers, array $payload, callable $extractText, callable $onText): array
    {
        $status = 0;
        $errorBody = '';
        $aborted = false;
        $buffer = '';
        $streamError = null;

        $ch = curl_init($url);

        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => (int) config('chat.connect_timeout'),
            CURLOPT_TIMEOUT => (int) config('chat.request_timeout'),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, string $data) use (&$status, &$errorBody, &$aborted, &$buffer, &$streamError, $extractText, $onText): int {
                if ($status === 0) {
                    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                }
                if ($status !== 200) {
                    $errorBody .= $data;

                    return strlen($data);
                }
                $buffer .= $data;
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $block = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);
                    $text = $extractText($block, $streamError);
                    if ($text !== null && $text !== '' && $onText($text) === false) {
                        $aborted = true;

                        return 0; // клиент отключился — обрываем передачу
                    }
                }

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

        if ($streamError !== null) {
            return ['ok' => false, 'status' => $status, 'error' => $streamError, 'aborted' => false];
        }

        return ['ok' => true, 'status' => 200, 'error' => '', 'aborted' => false];
    }
}
