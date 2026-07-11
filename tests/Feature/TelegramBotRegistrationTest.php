<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Telegram\TelegramBotRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class TelegramBotRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function internalHeaders(): array
    {
        Config::set('telegram.link_internal_api_token', 'test-internal-token');

        return ['Authorization' => 'Bearer test-internal-token'];
    }

    public function test_register_creates_user_with_telegram_id(): void
    {
        $response = $this->postJson('/api/internal/telegram/register', [
            'telegram_user_id' => 900001,
            'telegram_chat_id' => 900001,
            'telegram_username' => 'newbie',
            'telegram_first_name' => 'Alex',
            'offer_accepted' => true,
        ], $this->internalHeaders());

        $response->assertOk()->assertJsonPath('ok', true)->assertJsonPath('created', true);

        $user = User::query()->where('telegram_id', 900001)->first();
        $this->assertNotNull($user);
        $this->assertSame('Alex', $user->name);
        $this->assertTrue($user->hasVerifiedIdentity());
        $this->assertSame(
            TelegramBotRegistrationService::placeholderEmailForTelegramId(900001),
            $user->email
        );
    }

    public function test_register_is_idempotent_for_same_telegram_id(): void
    {
        User::factory()->create([
            'telegram_id' => 900002,
            'telegram_linked_at' => now(),
            'email' => TelegramBotRegistrationService::placeholderEmailForTelegramId(900002),
        ]);

        $response = $this->postJson('/api/internal/telegram/register', [
            'telegram_user_id' => 900002,
            'telegram_chat_id' => 900002,
            'offer_accepted' => true,
        ], $this->internalHeaders());

        $response->assertOk()->assertJsonPath('created', false);
        $this->assertSame(1, User::query()->where('telegram_id', 900002)->count());
    }

    public function test_status_reports_linked_user(): void
    {
        User::factory()->create(['telegram_id' => 900003, 'telegram_linked_at' => now()]);

        $this->postJson('/api/internal/telegram/bot/status', [
            'telegram_user_id' => 900003,
        ], $this->internalHeaders())
            ->assertOk()
            ->assertJsonPath('linked', true);

        $this->postJson('/api/internal/telegram/bot/status', [
            'telegram_user_id' => 900004,
        ], $this->internalHeaders())
            ->assertOk()
            ->assertJsonPath('linked', false);
    }

    public function test_telegram_user_with_linked_id_can_issue_trial_without_email(): void
    {
        $user = User::factory()->unverified()->create([
            'telegram_id' => 900005,
            'telegram_linked_at' => now(),
            'email' => TelegramBotRegistrationService::placeholderEmailForTelegramId(900005),
        ]);

        $this->assertTrue($user->hasVerifiedIdentity());
        $this->assertTrue($user->canSelfIssueCabinetTrial());
    }

    public function test_signed_telegram_cabinet_login_url_logs_user_in(): void
    {
        $user = User::factory()->create([
            'telegram_id' => 900006,
            'telegram_linked_at' => now(),
        ]);

        URL::forceRootUrl('https://nadezhda.space');

        $url = URL::temporarySignedRoute('auth.telegram_cabinet', now()->addMinutes(10), ['user' => $user->id]);

        $this->get($url)->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }
}
