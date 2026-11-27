<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Payments\PaymentProviders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Переключатель платёжек сайта и бота (админка «Платёжки», 28.11.2026: Platega недоступна из РФ — сайт на Wata). */
class PaymentProvidersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'xui.bundle_order' => [],
            'admin.username' => 'boss', 'admin.password' => 'boss-pass',
            'payments.telegram_bot_provider' => 'wata',
            'wata.access_token' => 'test-token',
            'wata.base_url' => 'https://wata.test/api/h2h/',
            'platega.merchant_id' => 'm', 'platega.secret' => 's',
        ]);
        Http::fake([
            'wata.test/api/h2h/links' => Http::response(['id' => 'lnk_1', 'url' => 'https://pay.wata.test/lnk_1', 'status' => 'Opened']),
            '*' => Http::response(['error' => 'unexpected'], 500),
        ]);
    }

    public function test_defaults_site_platega_bot_from_config(): void
    {
        $this->assertSame('platega', PaymentProviders::site());
        $this->assertSame('wata', PaymentProviders::bot());
    }

    public function test_admin_switches_providers(): void
    {
        $this->withSession(['admin_auth' => true])->get(route('admin.payment_providers'))->assertOk()->assertSee('Telegram-бот');

        $this->withSession(['admin_auth' => true])
            ->post(route('admin.payment_providers.update'), ['site' => 'wata', 'bot' => 'platega'])
            ->assertRedirect(route('admin.payment_providers'));

        $this->assertSame('wata', PaymentProviders::site());
        $this->assertSame('platega', PaymentProviders::bot());

        $this->withSession(['admin_auth' => true])
            ->post(route('admin.payment_providers.update'), ['site' => 'yoomoney', 'bot' => 'wata'])
            ->assertSessionHasErrors('site');
        $this->assertSame('wata', PaymentProviders::site());
    }

    public function test_guest_cannot_open_switch(): void
    {
        $this->get(route('admin.payment_providers'))->assertRedirect();
        $this->post(route('admin.payment_providers.update'), ['site' => 'wata', 'bot' => 'wata'])->assertRedirect();
        $this->assertNull(AppSetting::getValue(PaymentProviders::SITE_KEY));
    }

    public function test_cabinet_payment_goes_to_wata_when_switched(): void
    {
        PaymentProviders::set(PaymentProviders::SITE_KEY, 'wata');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('cabinet.payment.link'), ['term' => '1m', 'devices' => 0])
            ->assertOk()
            ->assertJsonPath('url', 'https://pay.wata.test/lnk_1');

        $order = PaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('wata', $order->provider);
        $this->assertSame('pending', $order->status);
        $this->assertSame('lnk_1', $order->provider_link_id);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/links') && $r['orderId'] === $order->order_id);
    }

    public function test_quick_buy_goes_to_wata_when_switched(): void
    {
        PaymentProviders::set(PaymentProviders::SITE_KEY, 'wata');

        $this->postJson(route('quick_buy.pay'), ['term' => '1m', 'email' => 'new-buyer@example.com'])
            ->assertOk()
            ->assertJsonPath('url', 'https://pay.wata.test/lnk_1');

        $order = PaymentOrder::query()->where('email', 'new-buyer@example.com')->firstOrFail();
        $this->assertSame('wata', $order->provider);
        $this->assertSame('lnk_1', $order->provider_link_id);
    }
}
