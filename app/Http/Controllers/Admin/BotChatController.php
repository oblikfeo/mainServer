<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotChatMessage;
use App\Models\User;
use App\Services\Telegram\TelegramBotRegistrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Просмотр диалогов клиентов с ИИ-ассистентом Telegram-бота (кнопка «Поддержка»).
 * Список сгруппирован по Telegram-пользователю; email показывается для привязанных.
 */
class BotChatController extends Controller
{
    public function index(): View
    {
        // Агрегируем по Telegram-юзеру: число сообщений, последняя активность,
        // были ли эскалации на оператора, актуальный username.
        $rows = BotChatMessage::query()
            ->selectRaw('telegram_user_id')
            ->selectRaw('COUNT(*) as messages_count')
            ->selectRaw('MAX(created_at) as last_at')
            ->selectRaw('MAX(id) as last_id')
            ->selectRaw('MAX(handoff) as had_handoff')
            ->groupBy('telegram_user_id')
            ->orderByDesc('last_at')
            ->paginate(30);

        // Подтягиваем username (последний известный), email и последнюю реплику одним заходом.
        $tgIds = collect($rows->items())->pluck('telegram_user_id')->all();

        $usernames = [];
        $lastTexts = [];
        if ($tgIds !== []) {
            $lastIds = collect($rows->items())->pluck('last_id')->all();
            foreach (BotChatMessage::query()->whereIn('id', $lastIds)->get() as $m) {
                $lastTexts[$m->telegram_user_id] = [
                    'role' => $m->role,
                    'content' => $m->content,
                ];
            }
            // Username берём из самой свежей записи, где он не пуст.
            foreach (
                BotChatMessage::query()
                    ->whereIn('telegram_user_id', $tgIds)
                    ->whereNotNull('telegram_username')
                    ->orderByDesc('id')
                    ->get(['telegram_user_id', 'telegram_username']) as $m
            ) {
                $usernames[$m->telegram_user_id] ??= $m->telegram_username;
            }
        }

        $emails = User::query()
            ->whereIn('telegram_id', $tgIds ?: [0])
            ->pluck('email', 'telegram_id')
            ->map(fn (?string $email) => $this->realEmail($email))
            ->all();

        return view('admin.bot_chat.index', [
            'rows' => $rows,
            'usernames' => $usernames,
            'emails' => $emails,
            'lastTexts' => $lastTexts,
        ]);
    }

    public function show(Request $request, int $telegramUserId): View
    {
        $messages = BotChatMessage::query()
            ->where('telegram_user_id', $telegramUserId)
            ->orderBy('id')
            ->get();

        abort_if($messages->isEmpty(), 404);

        $username = $messages->whereNotNull('telegram_username')->last()?->telegram_username;
        $email = $this->realEmail(User::query()->where('telegram_id', $telegramUserId)->value('email'));

        return view('admin.bot_chat.show', [
            'telegramUserId' => $telegramUserId,
            'username' => $username,
            'email' => $email,
            'messages' => $messages,
        ]);
    }

    /**
     * Возвращает email, только если он настоящий (не технический placeholder
     * для регистрации через бот или быстрой покупки). Иначе null.
     */
    private function realEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        $normalized = strtolower(trim($email));

        if (TelegramBotRegistrationService::isPlaceholderTelegramEmail($normalized)) {
            return null;
        }

        $quickBuyDomain = strtolower((string) config('payments.quick_buy.autogen_email_domain', 'buy.nadezhda.local'));
        if ($quickBuyDomain !== '' && str_ends_with($normalized, '@'.$quickBuyDomain)) {
            return null;
        }

        return $email;
    }
}
