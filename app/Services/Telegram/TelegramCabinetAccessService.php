<?php

namespace App\Services\Telegram;

use App\Models\Subscription;
use App\Models\TestKey;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Одноразовая ссылка входа в ЛК для Telegram (auth.via_token по токену подписки/ключа).
 */
final class TelegramCabinetAccessService
{
    public function loginUrlForTelegramUserId(int $telegramUserId): ?string
    {
        /** @var User|null $user */
        $user = User::query()->where('telegram_id', $telegramUserId)->first();
        if ($user === null) {
            return null;
        }

        return $this->loginUrlForUser($user);
    }

    public function loginUrlForUser(User $user): string
    {
        if (! (bool) config('marketing.happ_cabinet_link_enabled', true)) {
            return $this->fallbackPublicUrl();
        }

        $token = $this->resolveAuthToken($user);
        if ($token !== '') {
            return route('auth.via_token', ['token' => $token], absolute: true);
        }

        if ($user->telegram_id !== null) {
            return URL::temporarySignedRoute(
                'auth.telegram_cabinet',
                now()->addMinutes(30),
                ['user' => $user->id],
                absolute: true,
            );
        }

        return $this->fallbackPublicUrl();
    }

    private function resolveAuthToken(User $user): string
    {
        $nowMs = (int) (now()->getTimestamp() * 1000);

        /** @var Subscription|null $paid */
        $paid = $user->subscriptions()
            ->where('is_trial', false)
            ->where(function ($q) use ($nowMs) {
                $q->where('expiry_ms', '<=', 0)
                    ->orWhere('expiry_ms', '>', $nowMs);
            })
            ->orderByDesc('id')
            ->first();
        if ($paid !== null) {
            $token = trim((string) $paid->token);
            if ($token !== '') {
                return $token;
            }
        }

        $trial = $user->activeTrialSubscription();
        if ($trial !== null) {
            $token = trim((string) $trial->token);
            if ($token !== '') {
                return $token;
            }
        }

        /** @var TestKey|null $testKey */
        $testKey = TestKey::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();
        if ($testKey !== null) {
            $token = trim((string) $testKey->token);
            if ($token !== '') {
                return $token;
            }
        }

        /** @var Subscription|null $anySub */
        $anySub = $user->subscriptions()->orderByDesc('id')->first();
        if ($anySub !== null) {
            $token = trim((string) $anySub->token);
            if ($token !== '') {
                return $token;
            }
        }

        return '';
    }

    private function fallbackPublicUrl(): string
    {
        $url = trim((string) config('telegram.cabinet_mirror_url', ''));
        if ($url !== '') {
            return $url;
        }

        return rtrim((string) config('app.url'), '/');
    }
}
