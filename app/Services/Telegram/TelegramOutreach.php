<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Вызов HTTP API бота для рассылки по шаблону (ТЗ п.3).
 */
final class TelegramOutreach
{
    /**
     * @param  array<string, string|int|float|null>  $variables
     */
    public function notifyChat(int $telegramChatId, string $templateId, array $variables = []): bool
    {
        $url = rtrim((string) config('telegram.bot_notify_base_url', ''), '/');
        $secret = (string) config('telegram.bot_incoming_secret', '');

        if ($url === '' || $secret === '') {
            return false;
        }

        try {
            $res = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$secret,
                    'Accept' => 'application/json',
                ])
                ->asJson()
                ->post($url.'/internal/notify', [
                    'telegram_chat_id' => $telegramChatId,
                    'template_id' => $templateId,
                    'variables' => $variables,
                ]);

            return $res->successful();
        } catch (Throwable $e) {
            Log::warning('telegram outreach http failed', [
                'error' => $e->getMessage(),
                'template_id' => $templateId,
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, string|int|float|null>  $variables
     */
    public function notifyUserIfEligible(User $user, string $templateId, array $variables = []): bool
    {
        if ($user->telegram_id === null || $user->telegram_bot_blocked_at !== null) {
            return false;
        }

        return $this->notifyChat((int) $user->telegram_id, $templateId, $variables);
    }
}
