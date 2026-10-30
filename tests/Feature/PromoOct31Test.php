<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Account\AccountOverview;
use App\Services\Payments\FulfillPaidPaymentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromoOct31Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xui.bundle_order' => []]);
        Carbon::setTestNow('2026-10-31 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function register(string $email, string $code = 'oct31'): User
    {
        $this->post('/promo', [
            'email' => $email, 'password' => 'secret-pass-1', 'offer_accepted' => '1', 'promo_code' => $code,
        ])->assertRedirect();

        return User::query()->where('email', $email)->firstOrFail();
    }

    private function pay(User $user, int $days, int $devices = 0, int $deviceMonths = 0, string $id = 'ord_oct_1'): PaymentOrder
    {
        $order = PaymentOrder::query()->create([
            'order_id' => $id, 'user_id' => $user->id, 'purpose' => 'account', 'provider' => 'platega',
            'status' => 'pending', 'amount_rub' => 290, 'currency' => 'RUB', 'description' => 'Подписка · 1 месяц',
            'tariff_plan' => 'account', 'tariff_period' => '1m', 'days' => $days, 'devices' => $devices,
            'device_months' => $deviceMonths, 'quota_gb' => 0,
        ]);
        app(FulfillPaidPaymentOrder::class)->fulfill($order);

        return $order->fresh();
    }

    private function expires(User $user): string
    {
        return AccountOverview::forUser($user->fresh())['expires_at']->timezone('UTC')->format('d.m.Y H:i');
    }

    public function test_registration_gives_trial_and_pending_month_with_conditions_popup(): void
    {
        $user = $this->register('oct-a@example.com');

        $this->assertSame('OCT31', $user->registration_promo_code);
        $this->assertSame(30, $user->promo_bonus_days);
        $o = AccountOverview::forUser($user);
        $this->assertSame('trial', $o['state']);
        $this->assertSame(2, $o['device_limit']);
        $this->assertSame('03.11.2026 12:00', $this->expires($user));

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('Промокод OCT31 активирован')
            ->assertSee('полного доступа', false)
            ->assertSee('+1 месяц', false)
            ->assertSee('при первой оплате подписки', false);

        $this->actingAs($user)->get(route('cabinet.payment'))->assertOk()
            ->assertSee('в подарок к первой оплате')
            ->assertSee('"bonusDays":30');
    }

    public function test_first_payment_in_trial_adds_month_on_top_once(): void
    {
        $user = $this->register('oct-b@example.com');

        $order = $this->pay($user, 30);
        $this->assertSame(30, (int) $order->bonus_days);
        $this->assertStringContainsString('+ 30 дн. в подарок (OCT31)', (string) $order->description);
        $this->assertSame(0, $user->fresh()->promo_bonus_days);
        // 3 дня пробного + 30 оплаченных + 30 в подарок.
        $this->assertSame('02.01.2027 12:00', $this->expires($user));
        $this->assertSame('active', AccountOverview::forUser($user->fresh())['state']);

        // Вторая оплата — без подарка.
        $second = $this->pay($user, 30, id: 'ord_oct_2');
        $this->assertSame(0, (int) $second->bonus_days);
        $this->assertSame('01.02.2027 12:00', $this->expires($user));

        $this->actingAs($user->fresh())->get(route('cabinet.payment'))->assertOk()
            ->assertDontSee('в подарок к первой оплате')
            ->assertSee('"bonusDays":0');
    }

    public function test_first_payment_after_trial_expired_counts_from_payment_day(): void
    {
        $user = $this->register('oct-c@example.com');
        Carbon::setTestNow('2026-11-10 12:00:00');
        $this->assertSame('inactive', AccountOverview::forUser($user->fresh())['state']);

        $this->pay($user, 90);
        $this->assertSame('10.03.2027 12:00', $this->expires($user)); // 90 + 30 от 10.11
    }

    public function test_device_only_payment_does_not_spend_the_gift(): void
    {
        $user = $this->register('oct-d@example.com');

        $devicesOnly = $this->pay($user, 0, 1, 1);
        $this->assertSame(0, (int) $devicesOnly->bonus_days);
        $this->assertSame(30, $user->fresh()->promo_bonus_days);

        $this->assertSame(30, (int) $this->pay($user, 30, id: 'ord_oct_2')->bonus_days);
    }

    public function test_other_codes_have_no_gift(): void
    {
        $user = $this->register('tt@example.com', 'TIKTOK');
        $this->assertSame(0, $user->promo_bonus_days);
        $this->assertSame(0, (int) $this->pay($user, 30)->bonus_days);

        $this->actingAs($user)->get('/dashboard')->assertSee('Вам доступна тестовая подписка на 3 дня');
    }
}
