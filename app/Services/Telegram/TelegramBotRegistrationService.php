<?php

namespace App\Services\Telegram;

use App\Models\TelegramStartUtmLog;
use App\Models\User;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TelegramBotRegistrationService
{
    public const WELCOME_REGISTERED = 'Добро пожаловать в сервис «Надежда»! Аккаунт создан — пользуйтесь меню ниже: личный кабинет, пробный доступ, бонусы и поддержка.';

    public static function placeholderEmailForTelegramId(int $telegramUserId): string
    {
        return 'tg_'.$telegramUserId.'@telegram.nadezhda.local';
    }

    public static function isPlaceholderTelegramEmail(string $email): bool
    {
        return str_ends_with(strtolower(trim($email)), '@telegram.nadezhda.local');
    }

    /**
     * @return array{user: User, created: bool}
     */
    public function registerOrGet(
        int $telegramUserId,
        ?string $telegramUsername,
        ?string $telegramFirstName,
        ?string $referralParam,
    ): array {
        /** @var User|null $existing */
        $existing = User::query()->where('telegram_id', $telegramUserId)->first();
        if ($existing !== null) {
            return ['user' => $existing, 'created' => false];
        }

        if (TelegramAccountLinkService::telegramIdTakenByAnotherUser($telegramUserId, 0)) {
            throw new \RuntimeException('telegram_id_taken');
        }

        $name = $this->resolveDisplayName($telegramFirstName, $telegramUsername, $telegramUserId);
        $referredById = $this->resolveReferrerId($referralParam);

        $user = new User([
            'name' => $name,
            'email' => self::placeholderEmailForTelegramId($telegramUserId),
            'password' => Hash::make(Str::random(48)),
        ]);
        $user->forceFill([
            'telegram_id' => $telegramUserId,
            'telegram_username' => $telegramUsername !== null && $telegramUsername !== '' ? $telegramUsername : null,
            'telegram_linked_at' => now(),
            'telegram_bot_blocked_at' => null,
        ]);
        if ($referredById !== null) {
            $user->referred_by = $referredById;
        }
        $user->save();

        if ($referralParam !== null && trim($referralParam) !== '') {
            TelegramStartUtmLog::query()->create([
                'telegram_user_id' => $telegramUserId,
                'utm_param' => trim($referralParam),
            ]);
        }

        app(ReferralRewardService::class)->onReferredUserRegistered($user);

        return ['user' => $user->fresh() ?? $user, 'created' => true];
    }

    private function resolveDisplayName(?string $firstName, ?string $username, int $telegramUserId): string
    {
        $first = trim((string) $firstName);
        if ($first !== '') {
            return Str::limit($first, 255, '');
        }

        $uname = trim((string) $username);
        if ($uname !== '') {
            return Str::limit($uname, 255, '');
        }

        return 'tg_'.$telegramUserId;
    }

    private function resolveReferrerId(?string $referralParam): ?int
    {
        $code = trim((string) $referralParam);
        if ($code === '' || strlen($code) > 64) {
            return null;
        }

        /** @var User|null $referrer */
        $referrer = User::query()->where('referral_code', $code)->first();
        if ($referrer === null) {
            return null;
        }

        return (int) $referrer->id;
    }
}
