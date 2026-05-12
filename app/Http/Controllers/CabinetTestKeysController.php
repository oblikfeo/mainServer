<?php

namespace App\Http\Controllers;

use App\Models\TestKey;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Xui\XuiPanelException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CabinetTestKeysController extends Controller
{
    public function store(Request $request, TrialSubscriptionIssuer $issuer): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'test_key' => 'Чтобы получить тестовую подписку, подтвердите почту в профиле.',
            ]);
        }

        if ($user->hasActiveNonTrialSubscription()) {
            return back()->withErrors([
                'test_key' => 'Тестовая подписка недоступна: у вас уже есть активная платная подписка.',
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

        if ($user->activeTrialSubscription() !== null) {
            return back()->with('status', 'test-key-exists');
        }

        $referralSlot = (int) $user->referral_invitee_test_issues_remaining > 0;

        try {
            if ($referralSlot) {
                $issuer->issueFromCabinet($user, true);
                $user->forceFill([
                    'referral_invitee_test_issues_remaining' => max(
                        0,
                        (int) $user->referral_invitee_test_issues_remaining - 1
                    ),
                ])->save();
            } else {
                $issuer->issueFromCabinet($user, false);
            }
        } catch (XuiPanelException $e) {
            return back()->withErrors([
                'test_key' => 'Не удалось выдать тестовую подписку: '.$e->getMessage(),
            ]);
        } catch (\Throwable) {
            return back()->withErrors([
                'test_key' => 'Не удалось выдать тестовую подписку. Попробуйте позже или напишите в поддержку.',
            ]);
        }

        return back()->with('status', 'test-key-issued');
    }
}
