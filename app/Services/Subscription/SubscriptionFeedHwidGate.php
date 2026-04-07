<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Привязка подписки к устройствам Happ: заголовок X-Hwid при GET /sub/{token}.
 * До N разных HWID (N = поле devices), храним только sha256.
 *
 * При отказе отдаём 200 + announce (док. Happ): иначе клиент показывает только «403», тело не видно.
 */
final class SubscriptionFeedHwidGate
{
    /**
     * Заголовки, чтобы кэш не отдал ответ другого устройства (HWID в Vary).
     *
     * @return array<string, string>
     */
    public static function subscriptionNoStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'Vary' => 'X-Hwid',
        ];
    }

    /**
     * Сообщение в интерфейсе Happ через announce (тело + заголовок, base64 по доке).
     */
    public static function happAnnounceDenialResponse(string $message): Response
    {
        $message = Str::limit($message, 200, '');
        $b64 = 'base64:'.base64_encode($message);
        $body = "#announce: {$b64}\n";

        return new Response($body, 200, array_merge([
            'Content-Type' => 'text/plain; charset=utf-8',
            'announce' => $b64,
        ], self::subscriptionNoStoreHeaders()));
    }

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
            return self::happAnnounceDenialResponse(
                'Добавьте ссылку в приложение Happ (нужен идентификатор устройства).'
            );
        }

        $hash = hash('sha256', $hwid);

        return DB::transaction(function () use ($subscription, $hash, $max): ?Response {
            $row = Subscription::query()->whereKey($subscription->id)->lockForUpdate()->first();
            if ($row === null) {
                return new Response('Подписка не найдена.', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            $hashes = $row->bound_hwid_hashes;
            if (! is_array($hashes)) {
                $hashes = [];
            }
            $hashes = array_values(array_filter($hashes, static fn ($h) => is_string($h) && strlen($h) === 64));

            if (in_array($hash, $hashes, true)) {
                return null;
            }

            if (count($hashes) >= $max) {
                return self::happAnnounceDenialResponse('Превышено число соединений');
            }

            $hashes[] = $hash;
            $row->bound_hwid_hashes = $hashes;
            $row->save();

            return null;
        });
    }
}
