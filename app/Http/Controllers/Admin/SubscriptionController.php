<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Xui\XuiPanelException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function create(): View
    {
        return view('admin.subscription.create');
    }

    public function store(Request $request, CreateDualBundleSubscription $service): RedirectResponse
    {
        $data = $request->validate([
            'devices' => ['required', 'integer', 'min:1', 'max:5'],
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'gb' => ['required', 'integer', 'min:1', 'max:50000'],
        ]);

        try {
            $result = $service->create(
                (int) $data['devices'],
                (int) $data['days'],
                (int) $data['gb'],
            );
        } catch (XuiPanelException $e) {
            return back()
                ->withInput()
                ->withErrors(['xui' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.subscription.show', $result->subscription)
            ->with('subscription_result', [
                'subscription_url' => $result->subscriptionUrl,
                'fi_vless' => $result->fiVlessLine,
                'nl_vless' => $result->nlVlessLine,
                'decode_warning' => $result->decodeWarning,
            ]);
    }

    public function show(Subscription $subscription, CreateDualBundleSubscription $service): View
    {
        $payload = session('subscription_result');

        if (is_array($payload)) {
            $subscriptionUrl = $payload['subscription_url'] ?? route('subscription.feed', ['token' => $subscription->token]);
            $fiVless = $payload['fi_vless'] ?? '';
            $nlVless = $payload['nl_vless'] ?? '';
            $decodeWarning = $payload['decode_warning'] ?? null;
        } else {
            $subscriptionUrl = route('subscription.feed', ['token' => $subscription->token]);
            $decoded = $service->decodeLinesForSubscription($subscription);
            $fiVless = $decoded['fi'];
            $nlVless = $decoded['nl'];
            $decodeWarning = $decoded['warning'];
        }

        return view('admin.subscription.show', [
            'subscription' => $subscription,
            'subscriptionUrl' => $subscriptionUrl,
            'fiVless' => $fiVless,
            'nlVless' => $nlVless,
            'decodeWarning' => $decodeWarning,
        ]);
    }
}
