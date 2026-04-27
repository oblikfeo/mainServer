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
                'test_key' => 'Чтобы получить тестовую подписку, подтвердите почту в профиле.',
            ]);
        }

        // Если уже есть подписка — тестовая подписка не нужна
        if ($user->subscriptions()->exists()) {
            return back()->withErrors([
                'test_key' => 'Тестовая подписка недоступна: у вас уже есть подписка.',
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

        $defaultHours = (int) config('test_keys.default_hours', 8);

        try {
            if ((int) $user->referral_invitee_test_issues_remaining > 0) {
                $manager->issueForUser($user, $defaultHours, false);
                $user->forceFill([
                    'referral_invitee_test_issues_remaining' => max(
                        0,
                        (int) $user->referral_invitee_test_issues_remaining - 1
                    ),
                ])->save();
            } else {
                $manager->issueForUser($user, null);
            }
        } catch (\Throwable $e) {
            return back()->withErrors([
                'test_key' => 'Не удалось выдать тестовую подписку. Связка может быть не настроена.',
            ]);
        }

        return back()->with('status', 'test-key-issued');
    }
}

