<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Account\AccountOrderFactory;
use App\Services\Payments\FulfillPaidPaymentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Админка «Клиенты и платежи»: поиск, вкладки, карточка клиента. */
class AdminClientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xui.bundle_order' => [], 'admin.username' => 'boss', 'admin.password' => 'boss-pass']);
        Carbon::setTestNow('2026-11-16 07:30:00');
        $this->withSession(['admin_auth' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payingClient(string $email, string $term, int $devices): array
    {
        $u = User::factory()->create(['email' => $email, 'name' => strstr($email, '@', true)]);
        $order = AccountOrderFactory::create(AccountOrderFactory::quote(null, $term, $devices), ['user_id' => $u->id]);
        $sub = app(FulfillPaidPaymentOrder::class)->fulfill($order)['subscription'];

        return [$u, $sub, $order];
    }

    public function test_payments_tab_shows_who_paid_how_much_and_what(): void
    {
        [$u] = $this->payingClient('grom_13-55@mail.ru', '3m', 2);
        $u->forceFill(['telegram_username' => 'gromik'])->save();
        $this->payingClient('other@mail.ru', '1m', 3);

        $this->get('/admin/clients')->assertOk()
            ->assertSee('Клиенты и платежи')
            ->assertSee('grom_13-55@mail.ru')->assertSee('@gromik')
            ->assertSee('700 ₽')->assertSee('Подписка · 3 месяца')
            ->assertSee('16.11.2026 13:30') // Омск
            ->assertSee('other@mail.ru')
            ->assertDontSee('С даты');
    }

    public function test_search_by_email_nick_subscription_code_and_order(): void
    {
        [, $sub, $order] = $this->payingClient('grom_13-55@mail.ru', '3m', 2);
        $this->payingClient('other@mail.ru', '1m', 3);

        $this->get('/admin/clients?q=GROM')->assertOk()->assertSee('grom_13-55@mail.ru')->assertDontSee('other@mail.ru');
        $this->get('/admin/clients?q=%23'.$sub->public_code)->assertOk()->assertSee('grom_13-55@mail.ru')->assertDontSee('other@mail.ru');
        $this->get('/admin/clients?q='.$order->order_id)->assertOk()->assertSee('grom_13-55@mail.ru')->assertDontSee('other@mail.ru');
        $this->get('/admin/clients?tab=clients&q=other')->assertOk()->assertSee('other@mail.ru')->assertDontSee('grom_13-55@mail.ru');
        $this->get('/admin/clients?q=nobody-here')->assertOk()->assertSee('Ничего не найдено');
    }

    public function test_clients_tab_lists_users_with_subscription_and_totals(): void
    {
        $this->payingClient('grom_13-55@mail.ru', '3m', 2);
        User::factory()->create(['email' => 'never-paid@mail.ru']);

        $this->get('/admin/clients?tab=clients')->assertOk()
            ->assertSee('grom_13-55@mail.ru')->assertSee('активна')->assertSee('700 ₽')
            ->assertSee('never-paid@mail.ru')->assertSee('не платил')->assertSee('нет подписки');
    }

    public function test_client_card_shows_subscription_devices_and_payments(): void
    {
        [$u, $sub] = $this->payingClient('grom_13-55@mail.ru', '1m', 3);
        $sub->forceFill([
            'bound_hwid_hashes' => [str_repeat('a', 64)],
            'bound_hwid_meta' => [str_repeat('a', 64) => ['label' => 'iPhone 16', 'type' => 'iOS', 'ip' => '1.2.3.4', 'seen_at' => '2026-11-16T07:37:00Z']],
        ])->save();

        $this->get('/admin/clients/'.$u->id)->assertOk()
            ->assertSee('grom_13-55@mail.ru')->assertSee('#'.$sub->public_code)
            ->assertSee('iPhone 16')->assertSee('16.11.2026 13:37')
            ->assertSee('1 из 3')->assertSee('440 ₽')
            ->assertSee($sub->shareableSubUrl());
    }

    public function test_old_report_and_payments_lead_here(): void
    {
        $this->get('/admin/report')->assertRedirect('/admin/clients?tab=clients');
        $this->get('/admin/payments')->assertRedirect('/admin/clients');
        $this->get('/admin')->assertOk()->assertSee('Клиенты и платежи');
    }
}
