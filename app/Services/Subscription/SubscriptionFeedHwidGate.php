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
     * @return array{type: string, ip: string, seen_at: string}
     */
    private function buildDeviceMeta(Request $request): array
    {
        $ua = strtolower(trim((string) $request->userAgent()));
        $type = 'Неизвестно';
        if ($ua !== '') {
            if (str_contains($ua, 'android')) {
                $type = 'Android';
            } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios')) {
                $type = 'iOS';
            } elseif (str_contains($ua, 'windows')) {
                $type = 'Windows';
            } elseif (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
                $type = 'macOS';
            } elseif (str_contains($ua, 'linux')) {
                $type = 'Linux';
            }
        }

        $ip = trim((string) $request->ip());
        if ($ip === '') {
            $ip = '—';
        }

        return [
            'type' => $type,
            'ip' => $ip,
            'seen_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, array{type?: string, ip?: string, seen_at?: string}>
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
            $out[$hash] = [
                'type' => isset($meta['type']) ? (string) $meta['type'] : 'Неизвестно',
                'ip' => isset($meta['ip']) ? (string) $meta['ip'] : '—',
                'seen_at' => isset($meta['seen_at']) ? (string) $meta['seen_at'] : '',
            ];
        }

        return $out;
    }
}
