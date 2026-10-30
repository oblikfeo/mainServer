<?php

namespace Tests\Feature;

use App\Models\SupportChat;
use App\Models\User;
use App\Services\Chat\ChatStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupportWidgetTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<list<string>> ответы ассистента по очереди, каждый — набор кусков стрима */
    private array $answers = [];

    private int $aiCalls = 0;

    private int $tgId = 500;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'telegram.bot_notify_base_url' => 'http://bot.test',
            'telegram.bot_incoming_secret' => 'bot-secret',
            'telegram.link_internal_api_token' => 'internal-token',
        ]);

        $test = $this;
        $this->app->instance(ChatStreamer::class, new class($test) extends ChatStreamer
        {
            public function __construct(private SupportWidgetTest $t) {}

            public function stream(string $modelKey, array $messages, callable $onText, string $systemSuffix = ''): array
            {
                foreach ($this->t->nextAnswer($systemSuffix) as $chunk) {
                    $onText($chunk);
                }

                return ['ok' => true, 'status' => 200, 'error' => null, 'aborted' => false];
            }
        });

        Http::fake(['bot.test/*' => fn () => Http::response(['ok' => true, 'message_id' => ++$this->tgId])]);
    }

    /** @return list<string> */
    public function nextAnswer(string $suffix): array
    {
        if (str_contains($suffix, 'РЕЖИМ САММАРИ')) {
            return ['Клиент не может оплатить картой.'];
        }
        $this->aiCalls++;

        return array_shift($this->answers) ?? ['ok'];
    }

    private function send(?string $chat, string $text): string
    {
        return $this->post('/support/chat/message', ['chat' => $chat, 'text' => $text, 'page' => '/'])
            ->assertOk()->streamedContent();
    }

    private function chatIdFrom(string $sse): string
    {
        preg_match('/event: chat\ndata: (\{.*\})/', $sse, $m);

        return json_decode($m[1], true)['id'];
    }

    public function test_assistant_answers_and_dialog_is_restored(): void
    {
        $this->answers = [['Тариф ', '290 ₽ в месяц.']];
        $sse = $this->send(null, 'Сколько стоит?');
        $id = $this->chatIdFrom($sse);

        $this->assertStringContainsString('"text":"Тариф "', $sse);
        $this->assertStringContainsString('event: done', $sse);

        $this->getJson('/support/chat/'.$id.'/messages')->assertOk()
            ->assertJsonPath('status', 'ai')
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.1.content', 'Тариф 290 ₽ в месяц.');
    }

    public function test_handoff_marker_is_hidden_card_goes_to_telegram_and_operator_reply_comes_back(): void
    {
        $this->answers = [['Это решит человек. Передаю оператору. [[HAND', 'OFF]]']];
        $sse = $this->send(null, 'Не проходит оплата картой');
        $id = $this->chatIdFrom($sse);

        $this->assertStringNotContainsString('HAND', $sse);
        $this->assertStringContainsString('event: handoff', $sse);
        $chat = SupportChat::query()->where('public_id', $id)->firstOrFail();
        $this->assertSame('operator', $chat->status);
        $this->assertSame('Это решит человек. Передаю оператору.', $chat->messages()->where('role', 'assistant')->value('content'));
        $this->assertNotNull($chat->tg_card_message_id);
        Http::assertSent(fn (HttpRequest $r) => str_contains((string) $r['text'], '🆘 Чат с сайта #'.$chat->id)
            && str_contains((string) $r['text'], 'Суть: Клиент не может оплатить картой.')
            && $r->hasHeader('Authorization', 'Bearer bot-secret'));

        // После перевода ассистент молчит, сообщение уходит оператору ответом на карточку.
        $calls = $this->aiCalls;
        $sse = $this->send($id, 'Карта Мир, ошибка 3DS');
        $this->assertStringContainsString('event: queued', $sse);
        $this->assertSame($calls, $this->aiCalls);
        Http::assertSent(fn (HttpRequest $r) => str_contains((string) $r['text'], 'Карта Мир, ошибка 3DS') && $r['reply_to_message_id'] === $chat->tg_card_message_id);
        $forwardedTgId = (int) $chat->messages()->where('content', 'Карта Мир, ошибка 3DS')->value('tg_message_id');

        // Оператор отвечает reply на пересланное сообщение — ответ в виджете.
        $this->withToken('internal-token')->postJson('/api/internal/telegram/bot/web-support/reply', [
            'reply_to_message_id' => $forwardedTgId, 'text' => 'Попробуйте оплату через СБП.', 'operator_name' => 'Аня',
        ])->assertOk()->assertJsonPath('matched', true);

        $last = $this->getJson('/support/chat/'.$id.'/messages?after=0')->json('messages');
        $this->assertSame('operator', end($last)['role']);
        $this->assertSame('Попробуйте оплату через СБП.', end($last)['content']);

        $this->withToken('internal-token')->postJson('/api/internal/telegram/bot/web-support/reply', [
            'reply_to_message_id' => 999999, 'text' => 'мимо',
        ])->assertOk()->assertJsonPath('matched', false);
        $this->withToken('wrong')->postJson('/api/internal/telegram/bot/web-support/reply', [
            'reply_to_message_id' => $forwardedTgId, 'text' => 'x',
        ])->assertForbidden();
    }

    public function test_manual_handoff_and_contact_for_logged_in_user(): void
    {
        $user = User::factory()->create(['email' => 'client@example.com']);
        $id = $this->chatIdFrom($this->actingAs($user)->post('/support/chat/message', ['text' => 'Привет'])->streamedContent());

        $this->postJson('/support/chat/'.$id.'/handoff')->assertOk()->assertJsonPath('status', 'operator');
        Http::assertSent(fn (HttpRequest $r) => str_contains((string) $r['text'], 'client@example.com (аккаунт #'.$user->id)
            && str_contains((string) $r['text'], 'клиент позвал оператора'));

        $this->getJson('/support/chat/'.$id.'/messages')->assertJsonPath('has_email', true);
        $this->postJson('/support/chat/'.$id.'/contact', ['contact' => '@client_tg'])->assertOk();
        Http::assertSent(fn (HttpRequest $r) => str_contains((string) $r['text'], 'контакт клиента: @client_tg'));
    }

    public function test_limits_stop_the_assistant(): void
    {
        config(['chat.widget.chat_ai_limit' => 1]);
        $id = $this->chatIdFrom($this->send(null, 'Первый вопрос'));
        $sse = $this->send($id, 'Второй вопрос');

        $this->assertStringContainsString('event: notice', $sse);
        $this->assertSame(1, $this->aiCalls);
        $this->post('/support/chat/message', ['text' => str_repeat('a', 1001)])->assertSessionHasErrors('text');
        $this->getJson('/support/chat/unknown-chat-id/messages')->assertNotFound();
    }

    public function test_chat_page_is_closed_and_widget_is_on_home(): void
    {
        $this->get('/chat')->assertRedirect('/#support-chat');
        $this->get('/')->assertOk()->assertSee('id="ndsc"', false)->assertSee('data-support-chat-open', false);
    }
}
