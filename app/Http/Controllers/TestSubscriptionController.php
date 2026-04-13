<?php

namespace App\Http\Controllers;

use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Xui\XuiPanelException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestSubscriptionController extends Controller
{
    public function store(Request $request, CreateDualBundleSubscription $service): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'test_subscription' => 'Чтобы получить тестовую подписку, подтвердите почту в профиле.',
            ]);
        }

        if ($user->subscriptions()->exists()) {
            return back()->withErrors([
                'test_subscription' => 'Тестовая подписка уже была создана для этого аккаунта.',
            ]);
        }

        $devices = (int) env('TEST_SUBSCRIPTION_DEVICES', 1);
        $days = (int) env('TEST_SUBSCRIPTION_DAYS', 1);
        $quotaGb = (int) env('TEST_SUBSCRIPTION_QUOTA_GB', 2);

        try {
            $service->create($devices, $days, $quotaGb, $user->id);
        } catch (XuiPanelException $e) {
            return back()->withErrors([
                'test_subscription' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'test-subscription-created');
    }
}

