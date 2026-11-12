<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Account\AccountOrderFactory;
use App\Services\Account\AccountPricing;
use App\Services\Account\AccountSubscription;
use App\Services\Payments\FulfillPaidPaymentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

/** Акция «1 месяц + 10 устройств за 990 ₽». */
class AccountPromoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xui.bundle_order' => [], 'payments.account.promo.enabled' => true, 'payments.account.promo.amount_rub' => 990]);
        Carbon::setTestNow('2026-11-12 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fulfill(User $user, array $quote): array
    {
        $order = AccountOrderFactory::create($quote, ['user_id' => $user->id]);

        return app(FulfillPaidPaymentOrder::class)->fulfill($order);
    }

    public function test_promo_price_and_regular_price(): void
    {
        $promo = AccountPricing::promo();

        $this->assertSame(990, $promo['amount_rub']);
        $this->assertSame(10, $promo['devices']);
        $this->assertSame(290 + 8 * 150, $promo['regular_rub']);
        $this->assertSame(500, $promo['saving_rub']);
    }

    public function test_new_account_gets_month_and_ten_devices(): void
    {
        $user = User::factory()->create();
        $quote = AccountOrderFactory::quote(null, 'promo', 0, 0);

        $this->assertSame(990, $quote['amount_rub']);
        $this->assertSame(8, $quote['extra_devices']);
        $this->assertSame('Акция · 1 месяц + 10 устройств', AccountPricing::describe($quote));

        $sub = $this->fulfill($user, $quote)['subscription'];

        $this->assertSame(10, $sub->deviceLimit());
        $this->assertSame(Carbon::now()->addDays(30)->getTimestampMs(), (int) $sub->expiry_ms);

        // Через месяц и день акция закончилась — подписка тоже, лимит снова базовый.
        Carbon::setTestNow(Carbon::now()->addDays(31));
        $this->assertSame(2, $sub->fresh()->deviceLimit());
    }

    public function test_active_subscription_with_paid_slots_never_exceeds_ten(): void
    {
        $user = User::factory()->create();
        $sub = app(AccountSubscription::class)->applyPurchase($user, 30, 3, 90)['subscription'];
        $this->assertSame(5, $sub->deviceLimit());

        $sub = $this->fulfill($user, AccountOrderFactory::quote($sub, 'promo', 0, 0))['subscription'];

        $this->assertSame(10, $sub->deviceLimit());
        $this->assertSame(Carbon::now()->addDays(60)->getTimestampMs(), (int) $sub->expiry_ms);
    }

    public function test_trial_becomes_ten_devices_not_eleven(): void
    {
        $user = User::factory()->create();
        $trial = app(AccountSubscription::class)->grantHours($user, 8, 1, 5, isTrial: true)['subscription'];

        $sub = $this->fulfill($user, AccountOrderFactory::quote($trial, 'promo', 0, 0))['subscription'];

        $this->assertFalse($sub->is_trial);
        $this->assertSame(10, $sub->deviceLimit());
    }

    public function test_promo_unavailable_when_disabled_or_pointless(): void
    {
        $user = User::factory()->create();
        $sub = app(AccountSubscription::class)->applyPurchase($user, 30, 0, 0)['subscription'];
        $sub->devices = 30;
        $sub->save();
        $this->assertFalse(AccountPricing::promoAvailableFor($sub));

        config(['payments.account.promo.enabled' => false]);
        $this->assertNull(AccountPricing::promo());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('promo_unavailable');
        AccountOrderFactory::quote(null, 'promo', 0, 0);
    }
}
