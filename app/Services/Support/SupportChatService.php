<?php

namespace App\Services\Support;

use App\Models\SupportChat;
use App\Models\SupportChatMessage;
use App\Services\Account\AccountOverview;
use App\Services\Chat\ChatStreamer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Виджет поддержки ↔ группа поддержки в Telegram.
 *
 * Сообщения в группу отправляет бот (Laravel → POST {bot}/internal/support/web), Telegram ID каждого
 * сообщения сохраняется. Оператор отвечает reply на карточку или на любое пересланное сообщение —
 * бот присылает ответ в /internal/telegram/bot/web-support/reply, и он появляется в виджете.
 */
final class SupportChatService
{
    /** Маркер перевода на оператора в ответе ассистента (клиенту не показывается). */
    public const HANDOFF_MARKER = '[[HANDOFF]]';

    public function __construct(private readonly ChatStreamer $streamer) {}

    /**
     * История для ассистента: только реплики клиента и ассистента, с первой реплики клиента.
     *
     * @return list<array{role: string, content: string}>
     */
    public function aiHistory(SupportChat $chat): array
    {
        $limit = max(2, (int) config('chat.widget.history_messages', 20));
        $rows = $chat->messages()->whereIn('role', ['user', 'assistant'])->orderByDesc('id')->limit($limit)->get()->reverse();

        $out = [];
        foreach ($rows as $m) {
            $out[] = ['role' => (string) $m->role, 'content' => (string) $m->content];
        }
        while ($out !== [] && $out[0]['role'] !== 'user') {
            array_shift($out);
        }

        return $out;
    }

    /** Перевести диалог на оператора: карточка в группу поддержки. Повторный вызов ничего не делает. */
    public function handoff(SupportChat $chat, string $reason): void
    {
        if ($chat->isWithOperator()) {
            return;
        }
        $chat->forceFill(['status' => SupportChat::STATUS_OPERATOR, 'handoff_at' => now()])->save();

        $cardId = $this->postToGroup($this->card($chat, $reason));
        if ($cardId !== null) {
            $chat->forceFill(['tg_card_message_id' => $cardId])->save();
        } else {
            Log::warning('support.widget: карточка в Telegram не отправлена', ['chat' => $chat->id]);
        }
    }

    /** Сообщение клиента после перевода — в группу, ответом на карточку. */
    public function forwardUserMessage(SupportChat $chat, SupportChatMessage $message): void
    {
        $id = $this->postToGroup('💬 Сайт #'.$chat->id.' · клиент:'."\n".$message->content, $chat->tg_card_message_id);
        if ($id !== null) {
            $message->forceFill(['tg_message_id' => $id])->save();
        }
    }

    /** Клиент оставил почту/Telegram — сообщаем оператору. */
    public function contactUpdated(SupportChat $chat): void
    {
        if ($chat->isWithOperator()) {
            $this->postToGroup('📇 Сайт #'.$chat->id.' · контакт клиента: '.$chat->contact, $chat->tg_card_message_id);
        }
    }

    /**
     * Ответ оператора из группы (reply на карточку или на сообщение клиента).
     */
    public function operatorReply(int $replyToMessageId, string $text, ?string $operatorName): ?SupportChat
    {
        $chat = SupportChat::query()->where('tg_card_message_id', $replyToMessageId)->first();
        if ($chat === null) {
            $chat = SupportChatMessage::query()->where('tg_message_id', $replyToMessageId)->first()?->chat;
        }
        if ($chat === null) {
            return null;
        }

        $chat->messages()->create([
            'role' => 'operator',
            'content' => $text,
            'operator_name' => $operatorName !== null ? mb_substr($operatorName, 0, 64) : null,
        ]);
        $chat->forceFill(['status' => SupportChat::STATUS_OPERATOR, 'last_message_at' => now()])->save();

        $this->emailReply($chat, $text);

        return $chat;
    }

    /** Суть обращения для карточки (1–2 предложения от ассистента). Пусто — если не вышло. */
    public function summary(SupportChat $chat): string
    {
        $lines = [];
        foreach ($this->aiHistory($chat) as $m) {
            $lines[] = ($m['role'] === 'user' ? 'Клиент' : 'Ассистент').': '.$m['content'];
        }
        if ($lines === []) {
            return '';
        }
        $prompt = "Ниже переписка клиента с ассистентом поддержки. Сформулируй для оператора суть обращения клиента в 1-2 предложениях: в чём проблема или запрос и что уже пробовали. Только суть, без вступлений, обычным текстом.\n\n".implode("\n", $lines);

        $out = '';
        try {
            $this->streamer->stream((string) config('chat.default_model'), [['role' => 'user', 'content' => $prompt]], function (string $t) use (&$out): bool {
                $out .= $t;

                return true;
            }, (string) config('chat.telegram_summary_prompt', ''));
        } catch (Throwable $e) {
            Log::warning('support.widget: саммари не удалось', ['error' => $e->getMessage()]);

            return '';
        }

        return trim($out);
    }

    private function card(SupportChat $chat, string $reason): string
    {
        $who = 'гость (без аккаунта)';
        if ($chat->user !== null) {
            $o = AccountOverview::forUser($chat->user);
            $state = match ($o['state']) {
                AccountOverview::STATE_ACTIVE => 'оплачена'.($o['expires_at'] ? ' до '.$o['expires_at']->format('d.m.Y') : ', без срока'),
                AccountOverview::STATE_TRIAL => 'пробная до '.$o['expires_at']?->format('d.m.Y'),
                AccountOverview::STATE_INACTIVE => 'не активна',
                default => 'нет',
            };
            $who = $chat->user->email.' (аккаунт #'.$chat->user->id.', подписка: '.$state.')';
        }

        $gist = $this->summary($chat);
        if ($gist === '') {
            $lastUser = $chat->messages()->where('role', 'user')->orderByDesc('id')->value('content');
            $gist = $lastUser !== null ? (string) $lastUser : '(клиент ещё не описал вопрос)';
        }

        $transcript = [];
        foreach (array_slice($this->aiHistory($chat), -6) as $m) {
            $transcript[] = ($m['role'] === 'user' ? '👤 ' : '🤖 ').mb_strimwidth($m['content'], 0, 400, '…');
        }

        $text = '🆘 Чат с сайта #'.$chat->id.($reason === 'manual' ? ' · клиент позвал оператора' : ' · ассистент не справился')."\n"
            .'Клиент: '.$who."\n"
            .'Связь: '.($chat->contact ?: ($chat->replyEmail() ?? 'не оставил — ответ увидит только в окне чата'))."\n"
            .'————'."\n"
            .'Суть: '.$gist."\n"
            .($transcript !== [] ? '————'."\n".implode("\n", $transcript)."\n" : '')
            .'————'."\n"
            .'Ответьте reply на это сообщение — текст появится у клиента в чате на сайте.';

        return mb_strimwidth($text, 0, 4000, '…');
    }

    /** Отправить текст в группу поддержки через бота. Возвращает message_id или null. */
    private function postToGroup(string $text, ?int $replyTo = null): ?int
    {
        $url = rtrim((string) config('telegram.bot_notify_base_url', ''), '/');
        $secret = (string) config('telegram.bot_incoming_secret', '');
        if ($url === '' || $secret === '') {
            return null;
        }

        try {
            $res = Http::timeout(15)->withToken($secret)->acceptJson()->asJson()
                ->post($url.'/internal/support/web', ['text' => $text, 'reply_to_message_id' => $replyTo]);
            $id = (int) $res->json('message_id');

            return $res->successful() && $id > 0 ? $id : null;
        } catch (Throwable $e) {
            Log::warning('support.widget: бот недоступен', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function emailReply(SupportChat $chat, string $text): void
    {
        $email = $chat->replyEmail();
        if ($email === null) {
            return;
        }
        $brand = (string) config('marketing.brand_name', 'Надежда');
        $from = (string) (config('marketing.support_email') ?: config('mail.from.address'));
        $body = "Здравствуйте!\n\nОтвет поддержки {$brand} VPN на ваш вопрос в чате на сайте:\n\n{$text}\n\n"
            .'Продолжить разговор: '.rtrim((string) config('app.url'), '/')."/#support-chat\n";

        try {
            Mail::raw($body, function ($m) use ($email, $from, $brand): void {
                $m->to($email)->from($from, $brand.' · поддержка')->subject('Ответ поддержки '.$brand.' VPN');
            });
        } catch (Throwable $e) {
            Log::warning('support.widget: письмо с ответом не ушло', ['chat' => $chat->id, 'error' => $e->getMessage()]);
        }
    }
}
