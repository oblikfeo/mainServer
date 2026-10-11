<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\AccountPricing;
use App\Services\Account\AccountSubscription;
use App\Services\Account\DeviceSlots;
use App\Services\Payments\FulfillPaidPaymentOrder;
use App\Services\Subscription\SubscriptionFeedHwidGate;
use App\Services\Subscription\SubscriptionFeedHwidVerdict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class AccountSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Без панелей 3x-ui: подписка создаётся только в базе.
        config(['xui.bundle_order' => [], 'xui.feed_require_hwid' => true]);
        Carbon::setTestNow('2026-10-12 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function account(): AccountSubscription
    {
        return app(AccountSubscription::class);
    }

    private function requestFrom(string $hwid, string $ip = '10.0.0.1'): Request
    {
        $r = Request::create('/sub/x', 'GET', [], [], [], ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => 'Happ/4.12.0/ios/1']);
        $r->headers->set('x-hwid', $hwid);

        return $r;
    }

    public function test_price_is_term_discount_plus_flat_device_price(): void
    {
        $q = AccountPricing::quote(null, '3m', 2, 2);

        $this->assertSame(700, $q['base_rub']);
        $this->assertSame(600, $q['devices_rub']);
        $this->assertSame(1300, $q['amount_rub']);
        $this->assertSame(90, $q['days']);
        $this->assertSame(60, $q['device_days']);
    }

    public function test_six_months_devices_have_no_discount(): void
    {
        $q = AccountPricing::quote(null, '6m', 1, 6);

        $this->assertSame(1190 + 900, $q['amount_rub']);
    }

    public function test_term_is_required_without_active_subscription(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('term_required');

        AccountPricing::quote(null, null, 1, 1);
    }

    public function test_first_purchase_creates_subscription_and_second_extends_it(): void
    {
        $user = User::factory()->create();

        $first = $this->account()->applyPurchase($user, 30, 0, 0);
        $this->assertTrue($first['created']);
        $this->assertSame(2, $first['subscription']->devices);
        $this->assertSame(Carbon::now()->addDays(30)->getTimestampMs(), (int) $first['subscription']->expiry_ms);

        $second = $this->account()->applyPurchase($user, 90, 0, 0);
        $this->assertFalse($second['created']);
        $this->assertSame($first['subscription']->id, $second['subscription']->id);
        $this->assertSame(1, Subscription::query()->where('user_id', $user->id)->count());
        $this->assertSame(Carbon::now()->addDays(120)->getTimestampMs(), (int) $second['subscription']->expiry_ms);
    }

    public function test_database_refuses_second_subscription_for_account(): void
    {
        $user = User::factory()->create();
        $this->account()->applyPurchase($user, 30, 0, 0);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Subscription::query()->create([
            'user_id' => $user->id, 'token' => 'second-token', 'fi_sub_id' => '', 'nl_sub_id' => '',
            'quota_gb' => 0, 'expiry_ms' => 0, 'devices' => 2,
        ]);
    }

    public function test_device_slot_adds_to_limit_and_runs_out_on_its_own(): void
    {
        $user = User::factory()->create();
        $sub = $this->account()->applyPurchase($user, 90, 1, 30)['subscription'];
        $this->assertSame(3, $sub->deviceLimit());

        Carbon::setTestNow(Carbon::now()->addDays(29));
        $this->assertSame(3, $sub->fresh()->deviceLimit());

        Carbon::setTestNow(Carbon::now()->addDays(2));
        $this->assertSame(2, $sub->fresh()->deviceLimit(), 'слот на 30 дней закончился, подписка ещё идёт');
    }

    public function test_device_slot_freezes_while_subscription_is_inactive(): void
    {
        $user = User::factory()->create();
        $sub = $this->account()->applyPurchase($user, 30, 1, 60)['subscription'];

        // Подписка кончилась на 30-й день: слот не даёт место, но 30 дней на нём остались.
        Carbon::setTestNow(Carbon::now()->addDays(50));
        $sub = $sub->fresh();
        $this->assertTrue($sub->isExpired());
        $this->assertSame(2, $sub->deviceLimit());
        $this->assertSame(30 * DeviceSlots::DAY_MS, DeviceSlots::leftMs($sub->deviceSlots->first(), $sub));

        // Продлили подписку — слот идёт дальше со своими 30 днями.
        $sub = $this->account()->applyPurchase($user, 30, 0, 0)['subscription'];
        $this->assertSame(3, $sub->deviceLimit());
        Carbon::setTestNow(Carbon::now()->addDays(29));
        $this->assertSame(3, $sub->fresh()->deviceLimit());
        Carbon::setTestNow(Carbon::now()->addDays(2));
        $this->assertSame(2, $sub->fresh()->deviceLimit());
    }

    public function test_paying_devices_extends_existing_slots_before_creating_new(): void
    {
        $user = User::factory()->create();
        $this->account()->applyPurchase($user, 90, 1, 30);
        $sub = $this->account()->applyPurchase($user, 0, 2, 30)['subscription'];

        $this->assertSame(2, $sub->deviceSlots()->count(), 'один слот продлён, один создан');
        $left = $sub->deviceSlots->map(fn ($s) => DeviceSlots::leftMs($s, $sub))->sort()->values()->all();
        $this->assertSame([30 * DeviceSlots::DAY_MS, 60 * DeviceSlots::DAY_MS], $left);
    }

    public function test_devices_alone_cannot_be_bought_without_subscription(): void
    {
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->account()->applyPurchase($user, 0, 1, 30);
    }

    public function test_feed_gate_uses_limit_with_slots_and_blocks_devices_over_it(): void
    {
        $user = User::factory()->create();
        $sub = $this->account()->applyPurchase($user, 90, 1, 30)['subscription'];
        $gate = app(SubscriptionFeedHwidGate::class);

        foreach (['dev-a', 'dev-b', 'dev-c'] as $i => $hwid) {
            $this->assertSame(SubscriptionFeedHwidVerdict::Allowed, $gate->checkSubscription($this->requestFrom($hwid, '10.0.0.'.($i + 1)), $sub->fresh()));
        }
        $this->assertSame(SubscriptionFeedHwidVerdict::DeviceLimitExceeded, $gate->checkSubscription($this->requestFrom('dev-d', '10.0.0.9'), $sub->fresh()));

        // Слот закончился: третье привязанное устройство ждёт оплаты, первые два работают.
        Carbon::setTestNow(Carbon::now()->addDays(31));
        $this->assertSame(SubscriptionFeedHwidVerdict::Allowed, $gate->checkSubscription($this->requestFrom('dev-a', '10.0.0.1'), $sub->fresh()));
        $this->assertSame(SubscriptionFeedHwidVerdict::DeviceLimitExceeded, $gate->checkSubscription($this->requestFrom('dev-c', '10.0.0.3'), $sub->fresh()));
    }

    public function test_paid_account_order_extends_subscription_and_adds_devices(): void
    {
        $user = User::factory()->create();
        $order = PaymentOrder::query()->create([
            'order_id' => 'ord_acc_1', 'user_id' => $user->id, 'purpose' => 'account', 'provider' => 'platega',
            'status' => 'pending', 'amount_rub' => 1300, 'currency' => 'RUB', 'description' => 'test',
            'tariff_plan' => 'account', 'tariff_period' => '3m', 'days' => 90, 'devices' => 2, 'device_months' => 2, 'quota_gb' => 0,
        ]);

        $out = app(FulfillPaidPaymentOrder::class)->fulfill($order);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame($out['subscription']->id, $order->fresh()->subscription_id);
        $this->assertSame(4, $out['subscription']->deviceLimit());
        $this->assertSame(Carbon::now()->addDays(90)->getTimestampMs(), (int) $out['subscription']->expiry_ms);
    }

    public function test_legacy_new_family_order_never_creates_second_subscription(): void
    {
        $user = User::factory()->create();
        $existing = $this->account()->applyPurchase($user, 30, 0, 0)['subscription'];

        $order = PaymentOrder::query()->create([
            'order_id' => 'ord_legacy_1', 'user_id' => $user->id, 'purpose' => 'new', 'provider' => 'wata',
            'status' => 'pending', 'amount_rub' => 650, 'currency' => 'RUB', 'description' => 'test',
            'tariff_plan' => 'family', 'tariff_period' => '1 месяц', 'days' => 30, 'devices' => 5, 'quota_gb' => 250,
        ]);

        $out = app(FulfillPaidPaymentOrder::class)->fulfill($order);

        $this->assertSame($existing->id, $out['subscription']->id);
        $this->assertSame(1, Subscription::query()->where('user_id', $user->id)->count());
        $this->assertSame(5, $out['subscription']->deviceLimit(), '2 базовых + 3 доп. слота на тот же срок');
        $this->assertSame(Carbon::now()->addDays(60)->getTimestampMs(), (int) $out['subscription']->expiry_ms);
    }

    public function test_trial_becomes_paid_with_two_base_devices(): void
    {
        $user = User::factory()->create();
        $trial = $this->account()->grantHours($user, 8, 1, 5, isTrial: true)['subscription'];
        $this->assertTrue($trial->is_trial);
        $this->assertSame(1, $trial->devices);

        $paid = $this->account()->applyPurchase($user, 30, 0, 0)['subscription'];

        $this->assertSame($trial->id, $paid->id);
        $this->assertFalse($paid->is_trial);
        $this->assertSame(2, $paid->devices);
    }

    public function test_base_devices_above_standard_are_charged_on_renewal(): void
    {
        $user = User::factory()->create();
        $sub = $this->account()->applyPurchase($user, 30, 0, 0)['subscription'];
        $sub->forceFill(['devices' => 30])->save();

        $month = AccountPricing::quote($sub, '1m', 0, 0);
        $this->assertSame(28, $month['over_base_devices']);
        $this->assertSame(290 + 28 * 150, $month['amount_rub']);

        $three = AccountPricing::quote($sub, '3m', 0, 0);
        $this->assertSame(700 + 28 * 3 * 150, $three['amount_rub']);
    }

    public function test_referral_gift_device_stays_free_on_renewal(): void
    {
        $user = User::factory()->create();
        $sub = $this->account()->applyPurchase($user, 30, 0, 0)['subscription'];
        $sub->forceFill(['devices' => 3])->save();
        \App\Models\ReferralGrant::query()->create([
            'grant_key' => 'milestone_devices:'.$user->id,
            'kind' => \App\Models\ReferralGrant::KIND_MILESTONE_EXTRA_DEVICE,
            'beneficiary_user_id' => $user->id,
        ]);

        $this->assertSame(290, AccountPricing::quote($sub, '1m', 0, 0)['amount_rub']);
    }
}
