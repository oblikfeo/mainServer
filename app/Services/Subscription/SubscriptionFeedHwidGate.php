<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
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
                return new Response(
                    "Лимит устройств ({$max}) исчерпан.\n"
                    ."Это устройство не привязано к подписке. Доступ только с уже добавленных в Happ устройств.\n",
                    403,
                    ['Content-Type' => 'text/plain; charset=utf-8']
                );
            }

            $hashes[] = $hash;
            $row->bound_hwid_hashes = $hashes;
            $row->save();

            return null;
        });
    }
}
