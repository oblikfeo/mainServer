<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\Subscription\MergedSubscriptionFeedRenderer;
use App\Services\Subscription\SubscriptionFeedHwidGate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionFeedController extends Controller
{
    public function show(
        Request $request,
        string $token,
        SubscriptionFeedHwidGate $hwidGate,
        MergedSubscriptionFeedRenderer $renderer,
    ): Response {
        $subscription = Subscription::query()->where('token', $token)->first();
        if ($subscription === null) {
            abort(404);
        }

        $deny = $hwidGate->assertAllowed($request, $subscription);
        if ($deny !== null) {
            return $deny;
        }

        return $renderer->render($subscription);
    }
}
