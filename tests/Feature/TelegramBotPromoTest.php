<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\AccountOverview;
use App\Services\Telegram\TelegramBotRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class TelegramBotPromoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xui.bundle_order' => [], 'telegram.link_internal_api_token' => 'test-internal-token']);
        Carbon::setTestNow('2026-11-05 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function botUser(int $telegramId = 910001): User
    {
        return User::factory()->create([
            'telegram_id' => $telegramId,
            'telegram_linked_at' => now(),
            'email' => TelegramBotRegistrationService::placeholderEmailForTelegramId($telegramId),
        ]);
    }

    private function api(string $path, array $body): TestResponse
    {
        return $this->postJson('/api/internal/telegram/bot/'.$path, $body, ['Authorization' => 'Bearer test-internal-token']);
    }

    private function redeem(string $code): TestResponse
    {
        return $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => $code]);
    }

    public function test_requires_internal_token(): void
    {
        $this->postJson('/api/internal/telegram/bot/promo/redeem', ['telegram_user_id' => 1, 'code' => 'OCT31'])->assertForbidden();
    }

    public function test_unlinked_telegram_gets_not_linked(): void
    {
        $this->api('promo/redeem', ['telegram_user_id' => 999, 'code' => 'OCT31'])
            ->assertNotFound()
            ->assertJsonPath('error', 'not_linked');
    }

    public function test_new_bot_account_redeems_code_typed_in_russian_letters(): void
    {
        $user = $this->botUser();

        $this->redeem(' ост31 ')
            ->assertOk()
            ->assertJsonPath('code', 'OCT31')
            ->assertJsonPath('devices', 2)
            ->assertJsonPath('bonus_days', 30);

        $user->refresh();
        $this->assertSame('OCT31', $user->registration_promo_code);
        $this->assertSame(30, $user->promo_bonus_days);
        $this->assertFalse((bool) $user->promo_welcome_pending);
        $overview = AccountOverview::forUser($user);
        $this->assertSame('trial', $overview['state']);
        $this->assertSame('08.11.2026 12:00', $overview['expires_at']->timezone('UTC')->format('d.m.Y H:i'));

        $this->api('payment/subscriptions', ['telegram_user_id' => 910001])
            ->assertOk()
            ->assertJsonPath('account.promo_bonus_days', 30);
    }

    public function test_second_code_on_account_is_refused_and_changes_nothing(): void
    {
        $user = $this->botUser();
        $this->redeem('OCT31')->assertOk();
        $expiry = (int) Subscription::query()->where('user_id', $user->id)->value('expiry_ms');

        $this->redeem('TIKTOK')->assertStatus(422)->assertJsonPath('error', 'already_used');

        $this->assertSame($expiry, (int) Subscription::query()->where('user_id', $user->id)->value('expiry_ms'));
        $this->assertSame('OCT31', $user->fresh()->registration_promo_code);
    }

    public function test_unknown_code_changes_nothing_and_can_be_retried(): void
    {
        $user = $this->botUser();

        $this->redeem('NOPE')->assertStatus(422)->assertJsonPath('error', 'not_found');

        $this->assertNull($user->fresh()->registration_promo_code);
        $this->assertSame(0, Subscription::query()->where('user_id', $user->id)->count());

        $this->redeem('TIKTOK')->assertOk();
    }

    public function test_account_that_took_trial_is_not_new_any_more(): void
    {
        $user = $this->botUser();
        $this->api('trial/issue', ['telegram_user_id' => 910001])->assertOk();
        $sub = Subscription::query()->where('user_id', $user->id)->firstOrFail();

        $this->redeem('OCT31')->assertStatus(422)->assertJsonPath('error', 'not_new');

        $this->assertNull($user->fresh()->registration_promo_code);
        $this->assertSame((int) $sub->expiry_ms, (int) $sub->fresh()->expiry_ms);
    }

    public function test_register_reports_created_only_for_new_account(): void
    {
        $body = ['telegram_user_id' => 910002, 'telegram_chat_id' => 910002, 'offer_accepted' => true];
        $headers = ['Authorization' => 'Bearer test-internal-token'];

        $this->postJson('/api/internal/telegram/register', $body, $headers)->assertOk()->assertJsonPath('created', true);
        $this->postJson('/api/internal/telegram/register', $body, $headers)->assertOk()->assertJsonPath('created', false);
    }
}
