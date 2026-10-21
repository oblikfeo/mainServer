<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnglishPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_english_pages_open_without_russian_text(): void
    {
        foreach (['/en', '/en/login', '/en/register', '/en/forgot-password', '/en/agreement', '/en/privacy', '/en/terms'] as $url) {
            $html = $this->get($url)->assertOk()->assertHeader('Content-Type', 'text/html; charset=utf-8')->getContent();
            $this->assertDoesNotMatchRegularExpression('/[А-Яа-яЁё]/u', $html, $url);
            $this->assertStringNotContainsString('__CSRF__', $html, $url);
            $this->assertStringNotContainsString('__ALERT__', $html, $url);
        }
        $this->get('/en/dashboard')->assertNotFound();
    }

    public function test_login_from_english_page_lands_in_cabinet_and_errors_are_english(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-pass-1')]);

        $this->from('/en/login')->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertRedirect('/en/login');
        $this->get('/en/login')->assertSee('Wrong email or password');

        $this->from('/en/login')->post('/login', ['email' => $user->email, 'password' => 'secret-pass-1'])->assertRedirect(route('dashboard', absolute: false));
    }
}
