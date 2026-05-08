<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Models\TestKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Привязка подписки к устройствам Happ: заголовок X-Hwid при GET /sub/{token}.
 * До N разных HWID (N = поле devices), храним только sha256.
 */
final class SubscriptionFeedHwidGate
{
    /**
     * @return list<string>
     */
    private static function hwidHeaderNames(): array
    {
        return ['X-Hwid', 'X-HWID', 'Happ-Hwid', 'X-Device-Id'];
    }

    public static function peekHwidFromRequest(Request $request): ?string
    {
        foreach (self::hwidHeaderNames() as $name) {
            $v = $request->header($name);
            if (is_string($v)) {
                $v = trim($v);
                if ($v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }

    /**
     * null — можно отдавать подписку; иначе ответ с ошибкой.
     */
    public function assertAllowed(Request $request, Subscription $subscription): ?Response
    {
        if (! config('xui.feed_require_hwid', true)) {
            return null;
        }

        $max = max(0, (int) $subscription->devices);
        if ($max < 1) {
            return null;
        }

        $hwid = self::peekHwidFromRequest($request);
        if ($hwid === null) {
            return new Response(
                "Подписка защищена привязкой к устройству.\n"
                ."Добавьте ссылку в приложение Happ — обновление из браузера без идентификатора устройства недоступно.\n",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $hash = hash('sha256', $hwid);
        $meta = $this->buildDeviceMeta($request);

        return DB::transaction(function () use ($subscription, $hash, $max, $meta): ?Response {
            $row = Subscription::query()->whereKey($subscription->id)->lockForUpdate()->first();
            if ($row === null) {
                return new Response('Подписка не найдена.', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            $hashes = $row->bound_hwid_hashes;
            if (! is_array($hashes)) {
                $hashes = [];
            }
            $hashes = array_values(array_filter($hashes, static fn ($h) => is_string($h) && strlen($h) === 64));
            $metaMap = $this->sanitizeMetaMap($row->bound_hwid_meta);

            if (in_array($hash, $hashes, true)) {
                $metaMap[$hash] = $meta;
                $row->bound_hwid_meta = $metaMap;
                $row->save();

                return null;
            }

            if (count($hashes) >= $max) {
                return new Response(
                    "Превышено число соединений\n",
                    403,
                    ['Content-Type' => 'text/plain; charset=utf-8']
                );
            }

            $hashes[] = $hash;
            $row->bound_hwid_hashes = $hashes;
            $metaMap[$hash] = $meta;
            $row->bound_hwid_meta = $metaMap;
            $row->save();

            return null;
        });
    }

    /**
     * null — можно отдавать тестовую подписку; иначе ответ с ошибкой.
     */
    public function assertAllowedForTestKey(Request $request, TestKey $testKey): ?Response
    {
        if (! config('xui.feed_require_hwid', true)) {
            return null;
        }

        $max = max(0, (int) $testKey->limit_ip);
        if ($max < 1) {
            return null;
        }

        $hwid = self::peekHwidFromRequest($request);
        if ($hwid === null) {
            return new Response(
                "Подписка защищена привязкой к устройству.\n"
                ."Добавьте ссылку в приложение Happ — обновление из браузера без идентификатора устройства недоступно.\n",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $hash = hash('sha256', $hwid);
        $meta = $this->buildDeviceMeta($request);

        return DB::transaction(function () use ($testKey, $hash, $max, $meta): ?Response {
            $row = TestKey::query()->whereKey($testKey->id)->lockForUpdate()->first();
            if ($row === null) {
                return new Response('Тестовая подписка не найдена.', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            $hashes = $row->bound_hwid_hashes;
            if (! is_array($hashes)) {
                $hashes = [];
            }
            $hashes = array_values(array_filter($hashes, static fn ($h) => is_string($h) && strlen($h) === 64));
            $metaMap = $this->sanitizeMetaMap($row->bound_hwid_meta);

            if (in_array($hash, $hashes, true)) {
                $metaMap[$hash] = $meta;
                $row->bound_hwid_meta = $metaMap;
                $row->save();

                return null;
            }

            if (count($hashes) >= $max) {
                return new Response(
                    "Превышено число соединений\n",
                    403,
                    ['Content-Type' => 'text/plain; charset=utf-8']
                );
            }

            $hashes[] = $hash;
            $row->bound_hwid_hashes = $hashes;
            $metaMap[$hash] = $meta;
            $row->bound_hwid_meta = $metaMap;
            $row->save();

            return null;
        });
    }

    /**
     * Дополнительные заголовки с «человеческим» именем устройства (если клиент пришлёт).
     * Happ по умолчанию шлёт только X-Hwid; без своих заголовков точная модель iOS в UA обычно недоступна.
     *
     * @return list<string>
     */
    private static function deviceFriendlyNameHeaderNames(): array
    {
        return [
            'X-Happ-Device-Name',
            'X-Device-Name',
            'Happ-Device',
            'X-Device-Model',
        ];
    }

    public static function peekDeviceFriendlyName(Request $request): string
    {
        foreach (self::deviceFriendlyNameHeaderNames() as $name) {
            $v = $request->header($name);
            if (! is_string($v)) {
                continue;
            }
            $v = trim($v);
            if ($v === '') {
                continue;
            }
            if (function_exists('mb_strlen') && mb_strlen($v) > 96) {
                return mb_substr($v, 0, 96).'…';
            }
            if (strlen($v) > 96) {
                return substr($v, 0, 96).'…';
            }

            return $v;
        }

        return '';
    }

    /**
     * @return array{type: string, label: string, ip: string, seen_at: string}
     */
    private function buildDeviceMeta(Request $request): array
    {
        $uaRaw = (string) $request->userAgent();
        $uaLower = strtolower(trim($uaRaw));
        $type = 'Неизвестно';
        if ($uaLower !== '') {
            if (str_contains($uaLower, 'ipad')) {
                $type = 'iPad';
            } elseif (str_contains($uaLower, 'iphone')) {
                $type = 'iPhone';
            } elseif (str_contains($uaLower, 'android')) {
                $type = 'Android';
            } elseif (str_contains($uaLower, 'windows')) {
                $type = 'Windows';
            } elseif (str_contains($uaLower, 'macintosh') || str_contains($uaLower, 'mac os')) {
                $type = 'macOS';
            } elseif (str_contains($uaLower, 'linux')) {
                $type = 'Linux';
            } elseif (str_contains($uaLower, 'ios')) {
                $type = 'iOS';
            }
        }

        $friendly = self::peekDeviceFriendlyName($request);
        $androidModel = self::guessAndroidModelFromUa($uaRaw);
        $label = $friendly;
        if ($label === '') {
            if ($androidModel !== '' && $type === 'Android') {
                $label = 'Android · '.$androidModel;
            } elseif ($type === 'Windows' || $type === 'macOS' || $type === 'Linux') {
                $label = $type;
            } elseif ($type === 'iPhone' || $type === 'iPad') {
                $label = $type;
            } else {
                $label = $type !== 'Неизвестно' ? $type : 'Устройство';
            }
        }

        $ip = trim((string) $request->ip());
        if ($ip === '') {
            $ip = '—';
        }

        return [
            'type' => $type,
            'label' => $label,
            'ip' => $ip,
            'seen_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Типичный фрагмент Android UA: "… Linux; Android 14; SM-S918B Build/UP1A…"
     */
    private static function guessAndroidModelFromUa(string $ua): string
    {
        $ua = trim($ua);
        if ($ua === '' || stripos($ua, 'Android') === false) {
            return '';
        }
        if (preg_match('/Android\s+[\d._]+;\s*([^)]+?)\s*(?:Build\/|\))/i', $ua, $m)) {
            $model = trim(preg_replace('/\s+/', ' ', (string) $m[1]));
            if ($model !== '' && strlen($model) < 80) {
                return $model;
            }
        }

        return '';
    }

    /**
     * @return array<string, array{type?: string, label?: string, ip?: string, seen_at?: string}>
     */
    private function sanitizeMetaMap(mixed $metaMap): array
    {
        if (! is_array($metaMap)) {
            return [];
        }

        $out = [];
        foreach ($metaMap as $hash => $meta) {
            if (! is_string($hash) || strlen($hash) !== 64 || ! is_array($meta)) {
                continue;
            }
            $type = isset($meta['type']) ? (string) $meta['type'] : 'Неизвестно';
            $label = isset($meta['label']) ? trim((string) $meta['label']) : '';
            if ($label === '') {
                $label = $type !== '' && $type !== 'Неизвестно' ? $type : 'Устройство';
            }
            $out[$hash] = [
                'type' => $type,
                'label' => $label,
                'ip' => isset($meta['ip']) ? (string) $meta['ip'] : '—',
                'seen_at' => isset($meta['seen_at']) ? (string) $meta['seen_at'] : '',
            ];
        }

        return $out;
    }
}
