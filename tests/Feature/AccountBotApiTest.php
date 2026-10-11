<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Account\AccountSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountBotApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'xui.bundle_order' => [],
            'payments.telegram_bot_provider' => 'wata',
            'wata.access_token' => 'test-token',
        ]);
        Carbon::setTestNow('2026-10-12 12:00:00');
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function linkedUser(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['telegram_id' => 777001])->save();

        return $user;
    }

    public function test_catalog_returns_terms_and_device_price(): void
    {
        $this->getJson('/api/internal/telegram/bot/payment/catalog')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('terms.0.key', '1m')
            ->assertJsonPath('terms.1.amount_rub', 700)
            ->assertJsonPath('base_devices', 2)
            ->assertJsonPath('max_devices', 10)
            ->assertJsonPath('device_month_rub', 150)
            ->assertJsonCount(1, 'plans');
    }

    public function test_account_state_for_user_without_subscription(): void
    {
        $this->linkedUser();

        $this->postJson('/api/internal/telegram/bot/payment/subscriptions', ['telegram_user_id' => 777001])
            ->assertOk()
            ->assertJsonPath('account.state', 'none')
            ->assertJsonPath('account.base_devices', 2)
            ->assertJsonPath('account.max_devices', 10)
            ->assertJsonCount(0, 'items');
    }

    public function test_account_state_shows_paid_devices(): void
    {
        $user = $this->linkedUser();
        app(AccountSubscription::class)->applyPurchase($user, 30, 2, 30);

        $this->postJson('/api/internal/telegram/bot/payment/subscriptions', ['telegram_user_id' => 777001])
            ->assertOk()
            ->assertJsonPath('account.state', 'active')
            ->assertJsonPath('account.extra_devices', 2)
            ->assertJsonPath('account.expires', '11.11.2026')
            ->assertJsonPath('items.0.devices', 4);

        $this->postJson('/api/internal/telegram/bot/devices', ['telegram_user_id' => 777001])
            ->assertOk()
            ->assertJsonPath('items.0.slots', 4);
    }

    public function test_create_requires_term_without_active_subscription(): void
    {
        $this->linkedUser();

        $this->postJson('/api/internal/telegram/bot/payment/create', [
            'telegram_user_id' => 777001,
            'term' => 'none',
            'devices' => 3,
            'device_months' => 1,
            'payment_method' => 'sbp',
        ])->assertStatus(422)->assertJsonPath('error', 'term_required');
    }

    public function test_create_refuses_more_devices_without_paying_for_them(): void
    {
        $this->linkedUser();

        $this->postJson('/api/internal/telegram/bot/payment/create', [
            'telegram_user_id' => 777001,
            'term' => '1m',
            'devices' => 4,
            'device_months' => 0,
            'payment_method' => 'sbp',
        ])->assertStatus(422)->assertJsonPath('error', 'device_months_required');
    }

    public function test_old_bot_family_request_is_told_to_reopen_menu(): void
    {
        $this->linkedUser();

        $this->postJson('/api/internal/telegram/bot/payment/create', [
            'telegram_user_id' => 777001,
            'purpose' => 'new',
            'plan' => 'family',
            'period_index' => 0,
            'payment_method' => 'sbp',
        ])->assertStatus(422)->assertJsonPath('error', 'menu_outdated');
    }

    public function test_quick_buy_rejects_unknown_term(): void
    {
        config(['platega.merchant_id' => 'm', 'platega.secret' => 's', 'platega.api_key' => 'k']);

        $response = $this->postJson('/buy/pay', ['term' => '12m', 'devices' => 2, 'email' => 'a@example.com']);

        $this->assertContains($response->status(), [422, 503]);
        $this->assertDatabaseCount('payment_orders', 0);
    }

    public function test_landing_and_buy_pages_render_new_prices(): void
    {
        $this->get('/buy')->assertOk()->assertSee('Сколько устройств')->assertSee('data-term="3m"', false);
        $this->get('/')->assertOk()->assertSee('Больше устройств');
    }
}
