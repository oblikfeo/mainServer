<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\TestKey;
use App\Services\Subscription\MergedSubscriptionFeedRenderer;
use App\Services\Subscription\SubscriptionFeedHwidGate;
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
            $deny = $hwidGate->assertAllowed($request, $subscription);
            if ($deny !== null) {
                return $deny;
            }

            $mobile = $this->subscriptionFetchLikelyMobile($request);

            return $this->subFeedFormat() === 'xray_json'
                ? $xrayJsonFeedRenderer->render($subscription, $mobile)
                : $uriFeedRenderer->render($subscription, $mobile);
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

        $mobile = $this->subscriptionFetchLikelyMobile($request);

        return $this->subFeedFormat() === 'xray_json'
            ? $xrayJsonFeedRenderer->renderTestKey($testKey, $mobile)
            : $testKeyRenderer->render($testKey, $mobile);
    }

    private function subFeedFormat(): string
    {
        return strtolower(trim((string) config('xui.sub_feed_format', 'uri')));
    }

    /** Мобильный Happ на iOS/Android не показывает блок sub-info над анонсом — ниже дублируем CTA в #announce. */
    private function subscriptionFetchLikelyMobile(Request $request): bool
    {
        $ua = strtolower((string) $request->userAgent());

        return str_contains($ua, 'iphone')
            || str_contains($ua, 'ipad')
            || str_contains($ua, 'android')
            || str_contains($ua, 'mobile')
            || str_contains($ua, 'cfnetwork')
            || str_contains($ua, 'okhttp')
            || str_contains($ua, 'happ');
    }
}
