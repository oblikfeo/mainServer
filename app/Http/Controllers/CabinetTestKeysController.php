<?php

namespace App\Http\Controllers;

use App\Models\TestKey;
use App\Services\TestKeys\TestKeyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CabinetTestKeysController extends Controller
{
    public function store(Request $request, TestKeyManager $manager): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'test_key' => 'Чтобы получить тестовый ключ, подтвердите почту в профиле.',
            ]);
        }

        // Если уже есть подписка — тестовый ключ не нужен
        if ($user->subscriptions()->exists()) {
            return back()->withErrors([
                'test_key' => 'Тестовый ключ недоступен: у вас уже есть подписка.',
            ]);
        }

        $existing = TestKey::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->orderByDesc('id')
            ->first();

        if ($existing !== null && $existing->expires_at !== null && $existing->expires_at->isFuture()) {
            return back()->with('status', 'test-key-exists');
        }

        try {
            $manager->issueForUser($user, null);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'test_key' => 'Не удалось выдать тестовый ключ. Связка может быть не настроена.',
            ]);
        }

        return back()->with('status', 'test-key-issued');
    }
}

