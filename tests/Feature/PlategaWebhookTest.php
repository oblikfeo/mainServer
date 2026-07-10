<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Subscription\CreatedSubscriptionResult;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PlategaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_webhook_rejects_bad_auth(): void
    {
        config([
            'platega.merchant_id' => 'merchant-uuid',
            'platega.secret' => 'secret-key',
        ]);

        $this->postJson('/payments/platega/webhook', ['id' => 'tx-1', 'status' => 'CONFIRMED'])
            ->assertStatus(403);
    }

    public function test_webhook_confirms_order_and_fulfills_once(): void
    {
        config([
            'platega.merchant_id' => 'merchant-uuid',
            'platega.secret' => 'secret-key',
        ]);

        $user = User::factory()->create();
        $order = PaymentOrder::query()->create([
            'order_id' => 'ord_test_platega',
            'user_id' => $user->id,
            'subscription_id' => null,
            'purpose' => 'new',
            'provider' => 'platega',
            'status' => 'pending',
            'amount_rub' => 290,
            'currency' => 'RUB',
            'description' => 'test',
            'tariff_plan' => 'solo',
            'tariff_period' => '1 месяц',
            'days' => 30,
            'devices' => 2,
            'quota_gb' => 100,
            'provider_transaction_id' => 'tx-uuid-1',
        ]);

        $subscription = new Subscription([
            'user_id' => $user->id,
            'devices' => 2,
            'quota_gb' => 100,
            'expiry_ms' => (time() + 86400) * 1000,
            'token' => 'testtoken123',
        ]);
        $mock = Mockery::mock(CreateDualBundleSubscription::class);
        $mock->shouldReceive('create')
            ->once()
            ->andReturn(new CreatedSubscriptionResult(
                subscription: $subscription,
                subscriptionUrl: 'https://example.test/sub',
                fiVlessLine: '',
                nlVlessLine: '',
                decodeWarning: null,
            ));
        $this->instance(CreateDualBundleSubscription::class, $mock);

        $headers = [
            'X-MerchantId' => 'merchant-uuid',
            'X-Secret' => 'secret-key',
        ];
        $payload = [
            'id' => 'tx-uuid-1',
            'status' => 'CONFIRMED',
            'amount' => 290,
            'currency' => 'RUB',
            'payload' => 'ord_test_platega',
        ];

        $this->postJson('/payments/platega/webhook', $payload, $headers)->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);

        $this->postJson('/payments/platega/webhook', $payload, $headers)->assertOk();
        $this->assertSame(1, PaymentOrder::query()->where('status', 'paid')->count());
    }
}
