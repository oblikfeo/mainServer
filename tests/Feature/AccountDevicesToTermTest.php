<?php

namespace Tests\Feature;

use App\Models\DeviceSlot;
use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\AccountOrderFactory;
use App\Services\Account\AccountPricing;
use App\Services\Account\AccountSubscription;
use App\Services\Account\DeviceSlots;
use App\Services\Payments\FulfillPaidPaymentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * С 14.11.2026: доп. устройство действует до конца подписки. Новое устройство на оплаченной активной
 * подписке стоит device_month_rub за device_month_days, пропорционально оставшимся дням, до рубля.
 */
class AccountDevicesToTermTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xui.bundle_order' => [], 'payments.account.promo.enabled' => true, 'payments.account.promo.amount_rub' => 990]);
        Carbon::setTestNow('2026-11-14 12:00:00');
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

    private function fulfill(User $user, array $quote): Subscription
    {
        $order = AccountOrderFactory::create($quote, ['user_id' => $user->id]);

        return app(FulfillPaidPaymentOrder::class)->fulfill($order)['subscription']->fresh();
    }

    /** Оплаченная подписка на 3 месяца, с покупки прошло 2 недели (осталось 76 дней). */
    private function paidThreeMonthsTwoWeeksAgo(int $extraSlots = 0): array
    {
        $user = User::factory()->create();
        Carbon::setTestNow('2026-10-31 12:00:00');
        $sub = $this->fulfill($user, AccountOrderFactory::quote(null, '3m', 2 + $extraSlots));
        Carbon::setTestNow('2026-11-14 12:00:00');

        return [$user, $sub->fresh()];
    }

    /** Все оплаченные слоты кончаются вместе с подпиской (в пределах секунды). */
    private function assertSlotsEndWithSubscription(Subscription $sub, int $count): void
    {
        $sub = $sub->fresh();
        $rows = DeviceSlots::overview($sub);
        $this->assertCount($count, $rows);
        $untilEnd = (int) $sub->expiry_ms - DeviceSlots::nowMs();
        foreach ($rows as $row) {
            $this->assertEqualsWithDelta($untilEnd, $row['left_ms'], 1000);
        }
    }

    // ── Новый аккаунт ─────────────────────────────────────────────────────────

    public function test_new_account_prices(): void
    {
        $this->assertSame(290, AccountOrderFactory::quote(null, '1m', 2)['amount_rub']);
        $this->assertSame(440, AccountOrderFactory::quote(null, '1m', 3)['amount_rub']);
        $this->assertSame(700 + 2 * 3 * 150, AccountOrderFactory::quote(null, '3m', 4)['amount_rub']);
        $this->assertSame(990, AccountOrderFactory::quote(null, 'promo', 10)['amount_rub']);
    }

    public function test_new_account_devices_last_whole_term(): void
    {
        $user = User::factory()->create();
        $sub = $this->fulfill($user, AccountOrderFactory::quote(null, '3m', 4));

        $this->assertSame(4, $sub->deviceLimit());
        $this->assertSame(Carbon::now()->addDays(90)->getTimestampMs(), (int) $sub->expiry_ms);
        $this->assertSlotsEndWithSubscription($sub, 2);
    }

    // ── Пробный период → оплата ───────────────────────────────────────────────

    public function test_trial_pays_290_for_two_devices_not_440(): void
    {
        $user = User::factory()->create();
        $trial = $this->account()->grantHours($user, 8, 1, 5, isTrial: true)['subscription'];

        $q = AccountOrderFactory::quote($trial, '1m', 2);
        $this->assertSame(290, $q['amount_rub']);
        $this->assertSame(0, $q['extra_devices']);

        $sub = $this->fulfill($user, $q);
        $this->assertFalse($sub->is_trial);
        $this->assertSame(2, $sub->deviceLimit());
        $this->assertSame(Carbon::now()->addHours(8)->addDays(30)->getTimestampMs(), (int) $sub->expiry_ms);
    }

    public function test_trial_with_third_device_pays_one_month_of_it_and_slot_lasts_whole_term(): void
    {
        $user = User::factory()->create();
        $trial = $this->account()->grantHours($user, 8, 1, 5, isTrial: true)['subscription'];

        $q = AccountOrderFactory::quote($trial, '1m', 3);
        $this->assertSame(440, $q['amount_rub']); // остаток пробного периода не оплачивается

        $sub = $this->fulfill($user, $q);
        $this->assertSame(3, $sub->deviceLimit());
        $this->assertSlotsEndWithSubscription($sub, 1);
    }

    public function test_trial_to_promo_gives_ten_devices_for_990(): void
    {
        $user = User::factory()->create();
        $trial = $this->account()->grantHours($user, 8, 1, 5, isTrial: true)['subscription'];

        $q = AccountOrderFactory::quote($trial, 'promo', 0);
        $this->assertSame(990, $q['amount_rub']);

        $sub = $this->fulfill($user, $q);
        $this->assertFalse($sub->is_trial);
        $this->assertSame(10, $sub->deviceLimit());
        $this->assertSlotsEndWithSubscription($sub, 8);
    }

    public function test_trial_cannot_buy_devices_without_term(): void
    {
        $user = User::factory()->create();
        $trial = $this->account()->grantHours($user, 8, 1, 5, isTrial: true)['subscription'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('term_required');
        AccountOrderFactory::quote($trial, null, 3);
    }

    // ── Активная оплаченная подписка: устройство до конца срока ────────────────

    public function test_device_on_active_subscription_costs_rest_of_term(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo();
        $this->assertSame(76, AccountPricing::paidDaysLeft($sub));

        $q = AccountOrderFactory::quote($sub, null, 3);
        $this->assertSame(380, $q['amount_rub']); // 150 × 76 / 30
        $this->assertSame(1, $q['extra_devices']);
        $this->assertSame(0, $q['days']);
        $this->assertSame(760, AccountOrderFactory::quote($sub, null, 4)['amount_rub']);

        $expiryBefore = (int) $sub->expiry_ms;
        $sub = $this->fulfill($user, $q);
        $this->assertSame($expiryBefore, (int) $sub->expiry_ms); // срок подписки не меняется
        $this->assertSame(3, $sub->deviceLimit());
        $this->assertSlotsEndWithSubscription($sub, 1);

        // Подписка кончилась — устройство кончилось вместе с ней.
        Carbon::setTestNow(Carbon::createFromTimestampMs($expiryBefore)->addMinute());
        $this->assertSame(0, DeviceSlots::activeCount($sub->fresh()));
    }

    public function test_rest_of_term_is_rounded_to_rubles(): void
    {
        $this->assertSame(5, AccountPricing::restOfTermRub(1, 1));
        $this->assertSame(65, AccountPricing::restOfTermRub(1, 13));
        $this->assertSame(170, AccountPricing::restOfTermRub(2, 17));
        // Если цену поменяют на некруглую за день — копеек всё равно нет.
        config(['payments.account.device_month_rub' => 149]);
        $this->assertSame(65, AccountPricing::restOfTermRub(1, 13)); // 64,57 → 65
        $this->assertSame(5, AccountPricing::restOfTermRub(1, 1));   // 4,97 → 5
    }

    public function test_last_day_is_still_a_whole_day(): void
    {
        [, $sub] = $this->paidThreeMonthsTwoWeeksAgo();
        Carbon::setTestNow(Carbon::createFromTimestampMs((int) $sub->expiry_ms)->subHours(3));

        $this->assertSame(1, AccountPricing::paidDaysLeft($sub));
        $this->assertSame(5, AccountOrderFactory::quote($sub, null, 3)['amount_rub']);
    }

    public function test_nothing_to_pay_without_new_devices(): void
    {
        [, $sub] = $this->paidThreeMonthsTwoWeeksAgo(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nothing_to_pay');
        AccountOrderFactory::quote($sub, null, 3);
    }

    // ── Продление ─────────────────────────────────────────────────────────────

    public function test_renewal_keeps_devices_for_new_term(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo(1);

        $q = AccountOrderFactory::quote($sub, '1m', 3);
        $this->assertSame(290 + 150, $q['amount_rub']);

        $expiryBefore = (int) $sub->expiry_ms;
        $sub = $this->fulfill($user, $q);
        $this->assertSame($expiryBefore + 30 * DeviceSlots::DAY_MS, (int) $sub->expiry_ms);
        $this->assertSame(3, $sub->deviceLimit());
        $this->assertSlotsEndWithSubscription($sub, 1);
    }

    public function test_renewal_with_new_device_pays_its_rest_of_current_term(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo(1);

        $q = AccountOrderFactory::quote($sub, '1m', 4);
        $this->assertSame(290 + 2 * 150 + 380, $q['amount_rub']);

        $sub = $this->fulfill($user, $q);
        $this->assertSame(4, $sub->deviceLimit());
        $this->assertSlotsEndWithSubscription($sub, 2);
    }

    public function test_renewal_with_fewer_devices_lets_extra_ones_end_with_current_term(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo(2);
        $oldExpiry = (int) $sub->expiry_ms;

        $q = AccountOrderFactory::quote($sub, '1m', 2);
        $this->assertSame(290, $q['amount_rub']);
        $sub = $this->fulfill($user, $q);
        $this->assertSame(4, $sub->deviceLimit()); // до конца оплаченного срока устройства остаются

        Carbon::setTestNow(Carbon::createFromTimestampMs($oldExpiry)->addMinute());
        $this->assertSame(2, $sub->fresh()->deviceLimit()); // дальше — только базовые
    }

    public function test_renewal_pulls_up_old_short_device_slot(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo();
        // Слот по старым правилам: оплачен на 10 дней при подписке ещё на 76.
        DeviceSlots::add($sub, 1, 10);

        $sub = $this->fulfill($user, AccountOrderFactory::quote($sub->fresh(), '1m', 3));
        $this->assertSlotsEndWithSubscription($sub, 1);
    }

    public function test_expired_subscription_with_frozen_slot_renews_it(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo(1);
        Carbon::setTestNow(Carbon::createFromTimestampMs((int) $sub->expiry_ms)->addDays(5));
        $this->assertFalse(AccountPricing::isPaidActive($sub->fresh()));

        $q = AccountOrderFactory::quote($sub->fresh(), '1m', 3);
        $this->assertSame(440, $q['amount_rub']); // остатка нет — только срок + устройство на месяц

        $sub = $this->fulfill($user, $q);
        $this->assertSame(Carbon::now()->addDays(30)->getTimestampMs(), (int) $sub->expiry_ms);
        $this->assertSame(3, $sub->deviceLimit());
        $this->assertGreaterThanOrEqual((int) $sub->expiry_ms - DeviceSlots::nowMs(), DeviceSlots::overview($sub)->first()['left_ms']);
    }

    // ── Акция на активной подписке ────────────────────────────────────────────

    public function test_promo_on_active_subscription_adds_rest_for_new_devices(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo();

        $q = AccountOrderFactory::quote($sub, 'promo', 0);
        $this->assertSame(990 + 8 * 380, $q['amount_rub']);

        $sub = $this->fulfill($user, $q);
        $this->assertSame(10, $sub->deviceLimit());
        $this->assertSlotsEndWithSubscription($sub, 8);
    }

    public function test_promo_renewal_for_family_that_already_has_ten(): void
    {
        [, $sub] = $this->paidThreeMonthsTwoWeeksAgo(8);
        $this->assertSame(990, AccountOrderFactory::quote($sub, 'promo', 0)['amount_rub']);
        $this->assertSame(290 + 8 * 150, AccountOrderFactory::quote($sub, '1m', 10)['amount_rub']);
    }

    // ── Особые случаи ─────────────────────────────────────────────────────────

    public function test_subscription_without_expiry_is_refused(): void
    {
        $user = User::factory()->create();
        $sub = $this->account()->applyPurchase($user, 30, 0, 0)['subscription'];
        $sub->forceFill(['expiry_ms' => 0])->save();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no_expiry');
        AccountOrderFactory::quote($sub->fresh(), null, 3);
    }

    public function test_old_order_with_device_months_keeps_old_meaning(): void
    {
        [$user, $sub] = $this->paidThreeMonthsTwoWeeksAgo();
        $order = PaymentOrder::query()->create([
            'order_id' => 'ord_old_dev', 'user_id' => $user->id, 'purpose' => 'account', 'provider' => 'platega',
            'status' => 'pending', 'amount_rub' => 150, 'currency' => 'RUB', 'description' => 'old',
            'tariff_plan' => 'account', 'tariff_period' => 'devices', 'days' => 0, 'devices' => 1, 'device_months' => 1, 'quota_gb' => 0,
        ]);
        app(FulfillPaidPaymentOrder::class)->fulfill($order);

        $slot = DeviceSlot::query()->where('subscription_id', $sub->id)->firstOrFail();
        $this->assertSame(30 * DeviceSlots::DAY_MS, (int) $slot->paid_ms_left);
    }

    public function test_order_description_has_no_month_counters(): void
    {
        [, $sub] = $this->paidThreeMonthsTwoWeeksAgo();
        $this->assertSame('Доп. устройства: 1 до конца подписки (76 дн.)', AccountPricing::describe(AccountOrderFactory::quote($sub, null, 3)));
        $this->assertSame('Подписка · 1 месяц + доп. устройств: 2 + новые устройства за остаток срока (2 × 76 дн.)', AccountPricing::describe(AccountOrderFactory::quote($sub, '1m', 4)));
    }

    // ── Что видит пользователь ────────────────────────────────────────────────

    public function test_payment_page_for_trial_counts_two_base_devices(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->account()->grantHours($user, 8, 1, 5, isTrial: true);

        $html = $this->actingAs($user)->get('/dashboard/payment')->assertOk()->getContent();
        $cfg = $this->payConfig($html);
        $this->assertSame(2, $cfg['baseDevices']);
        $this->assertSame(1, $cfg['currentDevices']);
        $this->assertFalse($cfg['paidActive']);
        $this->assertSame(990, $cfg['promo']['amount_rub']);
        $this->assertStringContainsString('Пробный период', $html);
    }

    public function test_payment_page_for_active_shows_days_left(): void
    {
        [$user] = $this->paidThreeMonthsTwoWeeksAgo(1);
        $user->forceFill(['email_verified_at' => now()])->save();

        $cfg = $this->payConfig($this->actingAs($user)->get('/dashboard/payment')->assertOk()->getContent());
        $this->assertTrue($cfg['paidActive']);
        $this->assertSame(76, $cfg['restDays']);
        $this->assertSame(3, $cfg['currentDevices']);
        $this->assertSame(1, $cfg['paidExtra']);
        $this->assertSame('29.01.2027', $cfg['expiresAt']);
    }

    /** @return array<string, mixed> */
    private function payConfig(string $html): array
    {
        $this->assertSame(1, preg_match('/id="acc-pay" data-config="([^"]+)"/', $html, $m));

        return json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    }
}
