<?php

namespace App\Services\Telegram;

use App\Models\TelegramLinkSession;
use App\Models\User;
use Illuminate\Support\Str;

final class TelegramAccountLinkService
{
    public static function hashDeepLinkToken(string $plaintextToken): string
    {
        return hash_hmac('sha256', $plaintextToken, (string) config('app.key'));
    }

    public static function hashOtpCode(string $digits): string
    {
        return hash_hmac('sha256', $digits, (string) config('app.key'));
    }

    public static function telegramIdTakenByAnotherUser(int $telegramUserId, int $exceptUserId): bool
    {
        return User::query()
            ->where('telegram_id', $telegramUserId)
            ->where('id', '!=', $exceptUserId)
            ->exists();
    }

    /**
     * @return array{0: TelegramLinkSession, 1: string} session and plaintext token for one-time redirect
     */
    public static function createSession(User $user): array
    {
        $ttlMinutes = max(5, min(60, (int) config('telegram.link_session_ttl_minutes', 15)));

        TelegramLinkSession::query()->where('user_id', $user->id)->delete();

        $plain = Str::random(48);
        $session = TelegramLinkSession::create([
            'user_id' => $user->id,
            'token_hash' => self::hashDeepLinkToken($plain),
            'otp_code_hash' => null,
            'telegram_user_id' => null,
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return [$session, $plain];
    }

    public static function buildBotDeepLink(string $plaintextToken): string
    {
        $user = strtolower(trim(config('telegram.link_bot_username', '')));
        if ($user === '') {
            return '';
        }
        $user = ltrim($user, '@');

        return 'https://t.me/'.$user.'?start='.$plaintextToken;
    }
}
