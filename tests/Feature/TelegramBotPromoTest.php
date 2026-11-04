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

    public function test_requires_internal_token(): void
    {
        $this->postJson('/api/internal/telegram/bot/promo/status', ['telegram_user_id' => 1])->assertForbidden();
    }

    public function test_unlinked_telegram_gets_not_linked(): void
    {
        $this->api('promo/status', ['telegram_user_id' => 999])->assertNotFound()->assertJsonPath('error', 'not_linked');
        $this->api('promo/redeem', ['telegram_user_id' => 999, 'code' => 'OCT31'])->assertNotFound();
    }

    public function test_new_bot_account_redeems_code_typed_in_russian_letters(): void
    {
        $user = $this->botUser();

        $this->api('promo/status', ['telegram_user_id' => 910001])->assertOk()->assertJsonPath('blocked', null);

        $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => ' ост31 '])
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
        $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => 'OCT31'])->assertOk();
        $expiry = (int) Subscription::query()->where('user_id', $user->id)->value('expiry_ms');

        $this->api('promo/status', ['telegram_user_id' => 910001])
            ->assertOk()
            ->assertJsonPath('blocked', 'already_used');
        $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => 'TIKTOK'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'already_used');

        $this->assertSame($expiry, (int) Subscription::query()->where('user_id', $user->id)->value('expiry_ms'));
        $this->assertSame('OCT31', $user->fresh()->registration_promo_code);
    }

    public function test_unknown_code_changes_nothing(): void
    {
        $user = $this->botUser();

        $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => 'NOPE'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'not_found');

        $this->assertNull($user->fresh()->registration_promo_code);
        $this->assertSame(0, Subscription::query()->where('user_id', $user->id)->count());
    }

    public function test_account_with_paid_subscription_cannot_redeem(): void
    {
        $user = $this->botUser();
        $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => 'OCT31'])->assertOk();
        Subscription::query()->where('user_id', $user->id)->update(['is_trial' => false]);
        $user->forceFill(['registration_promo_code' => null, 'promo_bonus_days' => 0])->save();

        $this->api('promo/status', ['telegram_user_id' => 910001])->assertOk()->assertJsonPath('blocked', 'has_paid');
        $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => 'OCT31'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'has_paid');

        $this->assertNull($user->fresh()->registration_promo_code);
    }

    public function test_code_after_used_trial_extends_it_and_does_not_reopen_self_issued_trial(): void
    {
        $user = $this->botUser();
        $this->api('trial/issue', ['telegram_user_id' => 910001])->assertOk();
        $sub = Subscription::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1, (int) $sub->devices);

        Carbon::setTestNow('2026-11-06 12:00:00'); // пробный уже закончился
        $this->api('promo/redeem', ['telegram_user_id' => 910001, 'code' => 'OCT31'])
            ->assertOk()
            ->assertJsonPath('devices', 2);

        $this->assertSame(1, Subscription::query()->where('user_id', $user->id)->count());
        $sub->refresh();
        $this->assertSame(2, (int) $sub->devices);
        $this->assertSame('09.11.2026 12:00', $sub->expiresAt()->timezone('UTC')->format('d.m.Y H:i'));

        Carbon::setTestNow('2026-11-10 12:00:00'); // промо закончилось
        $this->assertFalse($user->fresh()->canSelfIssueCabinetTrial());
    }
}
