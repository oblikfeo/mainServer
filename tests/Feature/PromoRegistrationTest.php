<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\CreatedSubscriptionResult;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Xui\XuiPanelException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PromoRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_promo_page_shows_code_field_regular_register_does_not(): void
    {
        $this->get('/promo')
            ->assertOk()
            ->assertSee('name="promo_code"', false)
            ->assertSee('Регистрация + бонус', false);

        $this->get('/register')
            ->assertOk()
            ->assertDontSee('name="promo_code"', false)
            ->assertDontSee('Регистрация + бонус', false);
    }

    public function test_promo_registration_without_code_creates_user_without_trial(): void
    {
        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldNotReceive('issueFromPromo');
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $response = $this->post('/promo', [
            'email' => 'plain@example.com',
            'password' => 'password-password',
            'offer_accepted' => '1',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'plain@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->registration_promo_code);
        $this->assertFalse((bool) $user->promo_welcome_pending);
    }

    public function test_unknown_promo_code_is_rejected(): void
    {
        $this->from('/promo')->post('/promo', [
            'email' => 'bad@example.com',
            'password' => 'password-password',
            'offer_accepted' => '1',
            'promo_code' => 'NOPE',
        ])->assertSessionHasErrors('promo_code');

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'bad@example.com')->first());
    }

    public function test_valid_promo_code_issues_trial_and_pending_popup(): void
    {
        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldReceive('issueFromPromo')
            ->once()
            ->with(
                Mockery::on(fn ($user) => $user instanceof User && $user->email === 'promo@example.com'),
                7,
                '2026',
            )
            ->andReturn($this->dummyIssueResult());
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $response = $this->post('/promo', [
            'email' => 'promo@example.com',
            'password' => 'password-password',
            'offer_accepted' => '1',
            'promo_code' => ' 2026 ',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'promo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('2026', $user->registration_promo_code);
        $this->assertTrue((bool) $user->promo_welcome_pending);
    }

    public function test_regular_register_ignores_posted_promo_code(): void
    {
        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldNotReceive('issueFromPromo');
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $this->post('/register', [
            'email' => 'sneaky@example.com',
            'password' => 'password-password',
            'offer_accepted' => '1',
            'promo_code' => '2026',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'sneaky@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->registration_promo_code);
        $this->assertFalse((bool) $user->promo_welcome_pending);
    }

    public function test_failed_promo_issue_does_not_leave_user(): void
    {
        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldReceive('issueFromPromo')->once()->andThrow(new XuiPanelException('xui down'));
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $this->from('/promo')->post('/promo', [
            'email' => 'fail@example.com',
            'password' => 'password-password',
            'offer_accepted' => '1',
            'promo_code' => '2026',
        ])->assertSessionHasErrors('promo_code');

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'fail@example.com')->first());
    }

    public function test_promo_trial_does_not_consume_cabinet_self_issue_after_expiry(): void
    {
        $user = User::factory()->create();
        Subscription::query()->create([
            'user_id' => $user->id,
            'token' => 'promo-trial-token',
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 5,
            'expiry_ms' => (int) ((time() - 3600) * 1000),
            'devices' => 1,
            'is_trial' => true,
            'promo_code' => '2026',
        ]);

        $this->assertTrue($user->fresh()->canSelfIssueCabinetTrial());
    }

    public function test_welcome_popup_skip_and_claim(): void
    {
        $user = User::factory()->create([
            'registration_promo_code' => '2026',
            'promo_welcome_pending' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Вам доступна тестовая подписка на 7 дней', false);

        $this->actingAs($user)
            ->from('/dashboard')
            ->post(route('cabinet.promo_welcome'), ['action' => 'skip'])
            ->assertRedirect('/dashboard');

        $this->assertFalse((bool) $user->fresh()->promo_welcome_pending);

        $user->forceFill(['promo_welcome_pending' => true])->save();

        $this->actingAs($user)
            ->post(route('cabinet.promo_welcome'), ['action' => 'claim'])
            ->assertRedirect(route('dashboard', ['tab' => 'trial']).'#cabinet-trial');

        $this->assertFalse((bool) $user->fresh()->promo_welcome_pending);
    }

    public function test_unverified_user_with_promo_trial_sees_subscription_link(): void
    {
        $user = User::factory()->unverified()->create([
            'registration_promo_code' => '2026',
            'promo_welcome_pending' => false,
        ]);
        Subscription::query()->create([
            'user_id' => $user->id,
            'token' => 'promo-live-token',
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 5,
            'expiry_ms' => (int) ((time() + 86400) * 1000),
            'devices' => 1,
            'is_trial' => true,
            'promo_code' => '2026',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('/sub/promo-live-token', false)
            ->assertDontSee('Чтобы получить тестовую подписку, подтвердите почту', false);
    }

    private function dummyIssueResult(): CreatedSubscriptionResult
    {
        $subscription = new Subscription;

        return new CreatedSubscriptionResult($subscription, 'http://example.test/sub/x', '', '', null);
    }
}
