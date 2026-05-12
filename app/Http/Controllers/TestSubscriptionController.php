<?php

namespace App\Http\Controllers;

use App\Models\TestKey;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Xui\XuiPanelException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestSubscriptionController extends Controller
{
    public function store(Request $request, TrialSubscriptionIssuer $issuer): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'test_subscription' => 'Чтобы получить тестовую подписку, подтвердите почту в профиле.',
            ]);
        }

        if ($user->hasActiveNonTrialSubscription()) {
            return back()->withErrors([
                'test_subscription' => 'Тестовая подписка недоступна: у вас уже есть активная платная подписка.',
            ]);
        }

        $legacyKey = TestKey::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($legacyKey || $user->activeTrialSubscription() !== null) {
            return back()->withErrors([
                'test_subscription' => 'У вас уже есть активная тестовая подписка.',
            ]);
        }

        try {
            $issuer->issueFromCabinet($user, false);
        } catch (XuiPanelException $e) {
            return back()->withErrors([
                'test_subscription' => 'Не удалось создать тестовую подписку: '.$e->getMessage(),
            ]);
        } catch (\Throwable) {
            return back()->withErrors([
                'test_subscription' => 'Не удалось создать тестовую подписку. Попробуйте позже.',
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'test-subscription-created');
    }
}
