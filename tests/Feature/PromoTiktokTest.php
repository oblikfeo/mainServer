<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Account\AccountOverview;
use App\Services\Account\AccountSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromoTiktokTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xui.bundle_order' => []]);
        Carbon::setTestNow('2026-10-23 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tiktok_code_gives_three_days_full_access_then_requires_payment(): void
    {
        $this->post('/promo', [
            'email' => 'tiktok-user@example.com',
            'password' => 'secret-pass-1',
            'offer_accepted' => '1',
            'promo_code' => 'tiktok',
        ])->assertRedirect();

        $user = User::query()->where('email', 'tiktok-user@example.com')->firstOrFail();
        $this->assertSame('TIKTOK', $user->registration_promo_code);
        $this->assertTrue((bool) $user->promo_welcome_pending);

        $o = AccountOverview::forUser($user);
        $this->assertSame('trial', $o['state']);
        $this->assertSame(2, $o['device_limit']);
        $this->assertSame('26.10.2026 12:00', $o['expires_at']->timezone('UTC')->format('d.m.Y H:i'));

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Вам доступна тестовая подписка на 3 дня');

        Carbon::setTestNow('2026-10-27 12:00:00');
        $this->assertSame('inactive', AccountOverview::forUser($user->fresh())['state']);

        app(AccountSubscription::class)->applyPurchase($user->fresh(), 30, 0, 0);
        $o = AccountOverview::forUser($user->fresh());
        $this->assertSame('active', $o['state']);
        $this->assertSame(2, $o['device_limit']);
        $this->assertSame(1, \App\Models\Subscription::query()->where('user_id', $user->id)->count());
    }

    public function test_unknown_code_is_rejected(): void
    {
        $this->post('/promo', [
            'email' => 'nope@example.com', 'password' => 'secret-pass-1', 'offer_accepted' => '1', 'promo_code' => 'tiktok2',
        ])->assertSessionHasErrors('promo_code');
        $this->assertDatabaseMissing('users', ['email' => 'nope@example.com']);
    }
}
