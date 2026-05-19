<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\TestKey;
use App\Services\Subscription\MergedSubscriptionFeedRenderer;
use App\Services\Subscription\SubscriptionFeedHwidGate;
use App\Services\Subscription\SubscriptionFeedHwidVerdict;
use App\Services\Subscription\TestKeySubscriptionFeedRenderer;
use App\Services\Subscription\XrayJsonSubscriptionFeedRenderer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionFeedController extends Controller
{
    public function show(
        Request $request,
        string $token,
        SubscriptionFeedHwidGate $hwidGate,
        MergedSubscriptionFeedRenderer $uriFeedRenderer,
        XrayJsonSubscriptionFeedRenderer $xrayJsonFeedRenderer,
        TestKeySubscriptionFeedRenderer $testKeyRenderer,
    ): Response {
        $subscription = Subscription::query()->where('token', $token)->first();
        if ($subscription !== null) {
            $verdict = $hwidGate->checkSubscription($request, $subscription);
            $blocked = $this->responseForHwidVerdict(
                $verdict,
                $hwidGate,
                fn () => $uriFeedRenderer->renderDeviceLimitStubFeed($subscription),
            );
            if ($blocked !== null) {
                return $blocked;
            }

            return $this->subFeedFormat() === 'xray_json'
                ? $xrayJsonFeedRenderer->render($subscription)
                : $uriFeedRenderer->render($subscription);
        }

        $testKey = TestKey::query()
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($testKey === null) {
            abort(404);
        }

        $verdict = $hwidGate->checkTestKey($request, $testKey);
        $blocked = $this->responseForHwidVerdict(
            $verdict,
            $hwidGate,
            fn () => $testKeyRenderer->renderDeviceLimitStubFeed($testKey),
        );
        if ($blocked !== null) {
            return $blocked;
        }

        return $this->subFeedFormat() === 'xray_json'
            ? $xrayJsonFeedRenderer->renderTestKey($testKey)
            : $testKeyRenderer->render($testKey);
    }

    private function subFeedFormat(): string
    {
        return strtolower(trim((string) config('xui.sub_feed_format', 'uri')));
    }

    /**
     * @param  callable(): Response  $deviceLimitStub
     */
    private function responseForHwidVerdict(
        SubscriptionFeedHwidVerdict $verdict,
        SubscriptionFeedHwidGate $hwidGate,
        callable $deviceLimitStub,
    ): ?Response {
        return match ($verdict) {
            SubscriptionFeedHwidVerdict::Allowed => null,
            SubscriptionFeedHwidVerdict::MissingHwid => $hwidGate->missingHwidResponse(),
            SubscriptionFeedHwidVerdict::DeviceLimitExceeded => $deviceLimitStub(),
        };
    }
}
