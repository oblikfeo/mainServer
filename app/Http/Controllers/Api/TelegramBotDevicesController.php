<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\TestKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Управление привязанными устройствами (HWID) подписки/тест-ключа из Telegram-бота.
 * Пользователь резолвится по telegram_id; действия — только над своими подписками.
 */
final class TelegramBotDevicesController extends Controller
{
    private const NOT_LINKED = [
        'ok' => false,
        'error' => 'not_linked',
        'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
    ];

    public function list(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $items = [];

        $subscriptions = $user->subscriptions()->orderByDesc('created_at')->get();
        foreach ($subscriptions as $sub) {
            $slots = max(0, (int) $sub->devices);
            $devices = $this->devicesOf($sub->bound_hwid_hashes, $sub->bound_hwid_meta);

            // Пропускаем подписки без слотов устройств и истёкшие без привязок — чтобы не засорять меню.
            if ($slots < 1 && $devices === []) {
                continue;
            }
            if ($sub->isExpired() && $devices === []) {
                continue;
            }

            $items[] = [
                'scope' => 's',
                'id' => (int) $sub->id,
                'title' => '#'.$sub->public_code,
                'is_trial' => (bool) $sub->is_trial,
                'active' => ! $sub->isExpired(),
                'slots' => $slots,
                'bound' => count($devices),
                'created' => $sub->created_at?->timezone(config('app.timezone'))->format('d.m.Y'),
                'expires' => $sub->expiresAt()?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
                'devices' => $devices,
            ];
        }

        $testKeys = $user->testKeys()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();
        foreach ($testKeys as $tk) {
            $slots = max(0, (int) $tk->limit_ip);
            $devices = $this->devicesOf($tk->bound_hwid_hashes, $tk->bound_hwid_meta);
            if ($slots < 1 && $devices === []) {
                continue;
            }

            $items[] = [
                'scope' => 't',
                'id' => (int) $tk->id,
                'title' => 'Тестовая подписка',
                'is_trial' => true,
                'active' => true,
                'slots' => $slots,
                'bound' => count($devices),
                'created' => $tk->created_at?->timezone(config('app.timezone'))->format('d.m.Y'),
                'expires' => $tk->expires_at?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
                'devices' => $devices,
            ];
        }

        return response()->json([
            'ok' => true,
            'hwid_enforced' => filter_var((string) config('xui.feed_require_hwid', true), FILTER_VALIDATE_BOOL),
            'items' => $items,
        ]);
    }

    public function detach(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'scope' => ['required', 'in:s,t'],
            'id' => ['required', 'integer'],
            'hash_prefix' => ['required', 'string', 'regex:/^[a-f0-9]{8,64}$/'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $model = $this->ownedModel($user, (string) $data['scope'], (int) $data['id']);
        if ($model === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_found',
                'message' => 'Подписка не найдена.',
            ], 404);
        }

        $prefix = (string) $data['hash_prefix'];
        $hashes = $this->sanitizeHashes($model->bound_hwid_hashes);
        $matched = array_values(array_filter($hashes, static fn ($h) => str_starts_with($h, $prefix)));

        if (count($matched) !== 1) {
            return response()->json([
                'ok' => false,
                'error' => count($matched) === 0 ? 'device_not_found' : 'ambiguous',
                'message' => count($matched) === 0
                    ? 'Устройство уже отвязано.'
                    : 'Не удалось однозначно определить устройство. Обновите список.',
            ], 409);
        }

        $target = $matched[0];
        $model->bound_hwid_hashes = array_values(array_filter(
            $hashes,
            static fn ($h) => $h !== $target
        )) ?: null;

        $meta = $model->bound_hwid_meta;
        if (is_array($meta) && isset($meta[$target])) {
            unset($meta[$target]);
            $model->bound_hwid_meta = $meta === [] ? null : $meta;
        }
        $model->save();

        return response()->json([
            'ok' => true,
            'message' => 'Устройство отвязано.',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'scope' => ['required', 'in:s,t'],
            'id' => ['required', 'integer'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $model = $this->ownedModel($user, (string) $data['scope'], (int) $data['id']);
        if ($model === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_found',
                'message' => 'Подписка не найдена.',
            ], 404);
        }

        $model->bound_hwid_hashes = null;
        $model->bound_hwid_meta = null;
        $model->save();

        return response()->json([
            'ok' => true,
            'message' => 'Все привязки сброшены.',
        ]);
    }

    private function resolveUser(int $telegramUserId): ?User
    {
        return User::query()->where('telegram_id', $telegramUserId)->first();
    }

    private function ownedModel(User $user, string $scope, int $id): Subscription|TestKey|null
    {
        if ($scope === 's') {
            return $user->subscriptions()->whereKey($id)->first();
        }

        return $user->testKeys()
            ->whereKey($id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    private function sanitizeHashes($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(
            $raw,
            static fn ($h) => is_string($h) && strlen($h) === 64
        ));
    }

    /**
     * @param  mixed  $rawHashes
     * @param  mixed  $rawMeta
     * @return list<array<string, string>>
     */
    private function devicesOf($rawHashes, $rawMeta): array
    {
        $hashes = $this->sanitizeHashes($rawHashes);
        $metaMap = is_array($rawMeta) ? $rawMeta : [];
        $out = [];

        foreach ($hashes as $hash) {
            $meta = $metaMap[$hash] ?? null;
            $label = is_array($meta) ? trim((string) ($meta['label'] ?? '')) : '';
            $type = is_array($meta) ? trim((string) ($meta['type'] ?? '')) : '';
            $ip = is_array($meta) ? trim((string) ($meta['ip'] ?? '')) : '';
            $seenRaw = is_array($meta) ? trim((string) ($meta['seen_at'] ?? '')) : '';

            $seen = '';
            if ($seenRaw !== '') {
                try {
                    $seen = Carbon::parse($seenRaw)->timezone(config('app.timezone'))->format('d.m.Y H:i');
                } catch (\Throwable) {
                    $seen = $seenRaw;
                }
            }

            if ($label === '') {
                $label = $type !== '' ? $type : 'Устройство';
            }

            $out[] = [
                'hash_prefix' => substr($hash, 0, 16),
                'label' => $label,
                'type' => $type,
                'ip' => $ip,
                'seen' => $seen,
            ];
        }

        return $out;
    }
}
