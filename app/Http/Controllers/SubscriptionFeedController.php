<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\TestKey;
use App\Services\Subscription\MergedSubscriptionFeedRenderer;
use App\Services\Subscription\SubscriptionFeedHwidGate;
use App\Services\Subscription\TestKeySubscriptionFeedRenderer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionFeedController extends Controller
{
    public function show(
        Request $request,
        string $token,
        SubscriptionFeedHwidGate $hwidGate,
        MergedSubscriptionFeedRenderer $renderer,
        TestKeySubscriptionFeedRenderer $testKeyRenderer,
    ): Response {
        $subscription = Subscription::query()->where('token', $token)->first();
        if ($subscription !== null) {
            $deny = $hwidGate->assertAllowed($request, $subscription);
            if ($deny !== null) {
                return $deny;
            }

            return $renderer->render($subscription);
        }

        $testKey = TestKey::query()
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($testKey === null) {
            abort(404);
        }

        $deny = $hwidGate->assertAllowedForTestKey($request, $testKey);
        if ($deny !== null) {
            return $deny;
        }

        return $testKeyRenderer->render($testKey);
    }
}
