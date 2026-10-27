<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Marketing\MarketingStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarketingDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'admin.username' => 'boss', 'admin.password' => 'boss-pass',
            'admin.marketing_username' => 'market', 'admin.marketing_password' => 'market-pass',
            'xui.bundle_order' => [],
        ]);
        Carbon::setTestNow('2026-10-27 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function order(User $u, int $rub, string $at): void
    {
        PaymentOrder::query()->create([
            'order_id' => 'ord_'.uniqid(), 'user_id' => $u->id, 'purpose' => 'account', 'provider' => 'platega',
            'status' => 'paid', 'paid_at' => $at, 'amount_rub' => $rub, 'currency' => 'RUB', 'description' => 'x',
            'tariff_plan' => 'account', 'tariff_period' => '1m', 'days' => 30, 'devices' => 0, 'quota_gb' => 0,
        ]);
    }

    public function test_marketing_login_sees_only_marketing_page(): void
    {
        $this->post('/admin/login', ['username' => 'market', 'password' => 'market-pass'])->assertRedirect(route('admin.marketing'));
        $this->get('/admin/marketing')->assertOk()->assertSee('Маркетинг')->assertSee('Промокоды')->assertDontSee('Реферальная система');
        $this->get('/admin')->assertRedirect(route('admin.marketing'));
        $this->get('/admin/payments')->assertRedirect(route('admin.marketing'));
        $this->get('/admin/login')->assertRedirect(route('admin.marketing'));
    }

    public function test_main_admin_also_sees_marketing_and_wrong_password_fails(): void
    {
        $this->post('/admin/login', ['username' => 'market', 'password' => 'nope'])->assertSessionHasErrors('username');
        $this->get('/admin/marketing')->assertRedirect(route('admin.login'));

        $this->post('/admin/login', ['username' => 'boss', 'password' => 'boss-pass'])->assertRedirect(route('admin.marketing')); // вернулся туда, куда шёл
        $this->get('/admin/marketing')->assertOk()->assertSee('Реферальная система');
    }

    public function test_stats_count_promo_registrations_payments_and_conversion(): void
    {
        $a = User::factory()->create(['created_at' => '2026-10-20 10:00:00']);
        $a->forceFill(['registration_promo_code' => 'TIKTOK'])->save();
        $b = User::factory()->create(['created_at' => '2026-10-21 10:00:00']);
        $b->forceFill(['registration_promo_code' => 'TIKTOK'])->save();
        $c = User::factory()->create(['created_at' => '2026-10-25 10:00:00']);
        User::factory()->create(['created_at' => '2026-09-01 10:00:00']); // вне периода

        $this->order($a, 290, '2026-10-23 10:00:00');
        $this->order($a, 700, '2026-10-26 10:00:00');
        $this->order($c, 290, '2026-10-25 12:00:00');

        $d = app(MarketingStats::class)->build(Carbon::parse('2026-10-14'), Carbon::parse('2026-10-27'));

        $this->assertSame(3, $d['kpi']['registrations']['value']);
        $this->assertSame(3, $d['kpi']['payments']['value']);
        $this->assertSame(1280, $d['kpi']['revenue']['value']);
        $this->assertSame(2, $d['kpi']['new_payers']['value']);
        $this->assertEqualsWithDelta(66.7, $d['kpi']['conversion']['value'], 0.05);
        $this->assertSame(14, count($d['daily']));

        $tiktok = collect($d['promo'])->firstWhere('code', 'TIKTOK');
        $this->assertSame(2, $tiktok['registrations_period']);
        $this->assertSame(1, $tiktok['payers']);
        $this->assertSame(990, $tiktok['revenue']);
        $this->assertSame(50.0, $tiktok['conversion']);

        $promo = collect($d['sources'])->firstWhere('key', 'promo');
        $this->assertSame(2, $promo['registrations']);
        $this->assertSame(580, $d['payments']['new_revenue']);
        $this->assertSame(700, $d['payments']['repeat_revenue']);
    }
}
