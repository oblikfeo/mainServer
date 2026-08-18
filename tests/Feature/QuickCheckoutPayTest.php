<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\FulfillPaidPaymentOrder;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Subscription\CreatedSubscriptionResult;
use App\Services\Wata\WataH2hClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class QuickCheckoutPayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pay_does_not_create_user_until_payment(): void
    {
        config(['wata.access_token' => 'test-token']);
        $this->mockWataLink();

        $this->postJson('/buy/pay', [
            'plan' => 'solo',
            'period' => '1 месяц',
            'email' => 'retry@example.com',
        ])->assertOk()->assertJsonStructure(['url']);

        $this->assertSame(0, User::query()->count());
        $order = PaymentOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame('retry@example.com', $order->email);
        $this->assertSame('pending', $order->status);
        $this->assertNotEmpty($order->quick_buy_password);
    }

    public function test_pay_releases_unpaid_placeholder_and_allows_same_email(): void
    {
        config(['wata.access_token' => 'test-token']);
        $this->mockWataLink();

        $ghost = User::factory()->unverified()->create([
            'email' => 'ghost@example.com',
            'name' => 'User111111',
        ]);
        PaymentOrder::query()->create([
            'order_id' => 'ord_oldghost',
            'claim_token' => str_repeat('b', 48),
            'user_id' => $ghost->id,
            'subscription_id' => null,
            'purpose' => 'new',
            'provider' => 'wata',
            'status' => 'pending',
            'amount_rub' => 290,
            'currency' => 'RUB',
            'description' => 'old',
            'tariff_plan' => 'solo',
            'tariff_period' => '1 месяц',
            'days' => 30,
            'devices' => 2,
            'quota_gb' => 100,
        ]);

        $this->postJson('/buy/pay', [
            'plan' => 'solo',
            'period' => '1 месяц',
            'email' => 'ghost@example.com',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['email' => 'ghost@example.com']);
        $this->assertSame(0, User::query()->count());
        $this->assertTrue(PaymentOrder::query()->where('email', 'ghost@example.com')->whereNull('user_id')->exists());
    }

    public function test_pay_rejects_existing_real_account_email(): void
    {
        config(['wata.access_token' => 'test-token']);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/buy/pay', [
            'plan' => 'solo',
            'period' => '1 месяц',
            'email' => 'taken@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, PaymentOrder::query()->count());
    }

    public function test_fulfill_creates_user_from_order_email(): void
    {
        Mail::fake();
        $order = PaymentOrder::query()->create([
            'order_id' => 'ord_quick_new',
            'claim_token' => str_repeat('c', 48),
            'user_id' => null,
            'email' => 'paidnow@example.com',
            'quick_buy_password' => Crypt::encryptString('TempPass123'),
            'subscription_id' => null,
            'purpose' => 'new',
            'provider' => 'wata',
            'status' => 'pending',
            'amount_rub' => 290,
            'currency' => 'RUB',
            'description' => 'Подписка solo',
            'tariff_plan' => 'solo',
            'tariff_period' => '1 месяц',
            'days' => 30,
            'devices' => 2,
            'quota_gb' => 100,
        ]);

        $subscription = Subscription::query()->create([
            'user_id' => null,
            'devices' => 2,
            'quota_gb' => 100,
            'expiry_ms' => (time() + 86400) * 1000,
            'token' => 'tok_quick_new_1',
            'fi_sub_id' => 'fi-test-1',
            'nl_sub_id' => 'nl-test-1',
            'is_trial' => false,
        ]);

        $mock = Mockery::mock(CreateDualBundleSubscription::class);
        $mock->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (int $devices, int $days, int $quotaGb, int $userId) use ($subscription) {
                $this->assertGreaterThan(0, $userId);
                $subscription->user_id = $userId;
                $subscription->save();

                return new CreatedSubscriptionResult(
                    subscription: $subscription,
                    subscriptionUrl: 'https://example.test/sub/'.$subscription->token,
                    fiVlessLine: '',
                    nlVlessLine: '',
                    decodeWarning: null,
                );
            });
        $this->instance(CreateDualBundleSubscription::class, $mock);

        $result = app(FulfillPaidPaymentOrder::class)->fulfill($order);
        $order->refresh();

        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->user_id);
        $this->assertTrue(User::query()->where('email', 'paidnow@example.com')->exists());
        $this->assertSame($order->user_id, $result['subscription']->user_id);
    }

    private function mockWataLink(): void
    {
        $wata = Mockery::mock(WataH2hClient::class);
        $wata->shouldReceive('createPaymentLink')->once()->andReturn([
            'id' => '11111111-1111-1111-1111-111111111111',
            'url' => 'https://pay.example.test/x',
            'status' => 'Opened',
            'orderId' => null,
        ]);
        $this->instance(WataH2hClient::class, $wata);
    }
}
