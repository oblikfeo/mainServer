<?php

namespace App\Http\Controllers;

use App\Services\Subscription\CreateDualBundleSubscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CabinetController extends Controller
{
    public function index(Request $request, CreateDualBundleSubscription $subscriptionService): View
    {
        $user = $request->user();
        $subscriptions = $user->subscriptions()->orderByDesc('created_at')->get();

        $items = [];
        foreach ($subscriptions as $subscription) {
            $decoded = $subscriptionService->decodeLinesForSubscription($subscription);
            $items[] = [
                'subscription' => $subscription,
                'subscriptionUrl' => url('/sub/'.$subscription->token),
                'fiVless' => $decoded['fi'],
                'nlVless' => $decoded['nl'],
                'decodeWarning' => $decoded['warning'] ?? null,
            ];
        }

        return view('cabinet.index', [
            'items' => $items,
        ]);
    }
}

