<?php

namespace Tests\Feature\Auth;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password-password',
            'offer_accepted' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_with_valid_referral_sets_referred_by(): void
    {
        $referrer = User::factory()->create();

        $this->get('/register?ref='.$referrer->referral_code);

        $this->post('/register', [
            'email' => 'invited@example.com',
            'password' => 'password-password',
            'offer_accepted' => '1',
        ]);

        $this->assertAuthenticated();
        $invited = User::query()->where('email', 'invited@example.com')->first();
        $this->assertNotNull($invited);
        $this->assertSame($referrer->id, $invited->referred_by);
    }

    public function test_profile_shows_referral_counts(): void
    {
        $referrer = User::factory()->create();
        $child = User::factory()->create();
        $child->forceFill(['referred_by' => $referrer->id])->save();
        Purchase::query()->create([
            'user_id' => $child->id,
            'amount_rub' => 100,
            'currency' => 'RUB',
            'paid_at' => now(),
            'description' => 'Тест',
        ]);

        $response = $this->actingAs($referrer)->get('/dashboard/profile');

        $response->assertOk();
        $response->assertSee('Реферальная программа', false);
        $response->assertSee('Зарегалось', false);
        $response->assertSee('Оплатили', false);
        $response->assertDontSee('Поделитесь ссылкой', false);
        $response->assertDontSee('register?ref', false);
    }
}
