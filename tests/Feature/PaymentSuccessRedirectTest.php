<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSuccessRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_spasibo_renders_same_done_page_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $claim = str_repeat('a', 48);

        PaymentOrder::query()->create([
            'order_id' => 'ord_test123',
            'claim_token' => $claim,
            'user_id' => $user->id,
            'subscription_id' => null,
            'purpose' => 'new',
            'provider' => 'wata',
            'status' => 'paid',
            'amount_rub' => 100,
            'currency' => 'RUB',
            'description' => 'test',
            'tariff_plan' => 'solo',
            'tariff_period' => '1m',
            'days' => 30,
            'devices' => 2,
            'quota_gb' => 100,
        ]);

        $response = $this->actingAs($user)->get('/spasibo');

        $response->assertOk();
        $response->assertViewIs('quick-buy.done');
        $response->assertSee('Подписка', false);
    }

    public function test_spasibo_redirects_guest_to_login(): void
    {
        $this->get('/spasibo')->assertRedirect(route('login'));
    }
}
