<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\TestKey;
use App\Services\Subscription\CreateDualBundleSubscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CabinetController extends Controller
{
    public function index(Request $request, CreateDualBundleSubscription $subscriptionService): View
    {
        $user = $request->user();
        $nowMs = (int) (now()->getTimestamp() * 1000);

        $subscriptions = $user->subscriptions()->orderByDesc('created_at')->get();

        $items = [];
        foreach ($subscriptions->where('is_trial', false) as $subscription) {
            $decoded = $subscriptionService->decodeLinesForSubscription($subscription);
            $items[] = [
                'subscription' => $subscription,
                'subscriptionUrl' => url('/sub/'.$subscription->token),
                'fiVless' => $decoded['fi'],
                'nlVless' => $decoded['nl'],
                'decodeWarning' => $decoded['warning'] ?? null,
            ];
        }

        /** @var \Illuminate\Support\Collection<int, Subscription> */
        $activeTrialSubscriptions = $subscriptions
            ->filter(fn (Subscription $s) => $s->is_trial && (int) $s->expiry_ms > $nowMs)
            ->values();

        return view('cabinet.index', [
            'items' => $items,
            'activeTrialSubscriptions' => $activeTrialSubscriptions,
            'activeTestKeys' => TestKey::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->get(),
        ]);
    }
}

