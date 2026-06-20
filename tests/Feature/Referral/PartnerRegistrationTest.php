<?php

namespace Tests\Feature\Referral;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_landing_sets_partner_referral_on_registration(): void
    {
        $partner = User::factory()->create([
            'email' => 'ivanova97@list.ru',
        ]);

        $response = $this->get('/Reset');
        $response->assertOk();
        $response->assertSee('partners/reset/logo.png', false);
        $response->assertSee('alt="Reset"', false);
        $response->assertDontSee('Партнёрское приглашение', false);
        $response->assertDontSee('Создать аккаунт', false);

        $register = $this->post('/register', [
            'email' => 'invited@example.com',
            'password' => 'password123',
            'offer_accepted' => '1',
        ]);

        $register->assertRedirect(route('dashboard'));

        $invited = User::query()->where('email', 'invited@example.com')->first();
        $this->assertNotNull($invited);
        $this->assertSame((int) $partner->id, (int) $invited->referred_by);
    }

    public function test_regular_registration_without_ref_is_not_attributed_to_partner(): void
    {
        User::factory()->create([
            'email' => 'ivanova97@list.ru',
        ]);

        $this->post('/register', [
            'email' => 'plain@example.com',
            'password' => 'password123',
            'offer_accepted' => '1',
        ]);

        $user = User::query()->where('email', 'plain@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->referred_by);
    }
}
