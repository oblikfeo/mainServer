<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class MergedSubscriptionFeedRenderer
{
    private const BYTES_PER_GB = 1_073_741_824;

    public function __construct(
        private readonly SubscriptionBundleCollector $bundleCollector,
    ) {}

    public function render(Subscription $sub): Response
    {
        if (ExpiredSubscriptionVlessStubs::shouldUse($sub)) {
            return $this->renderExpiredStubFeed($sub);
        }

        $nodes = config('xui.nodes', []);
        $bundleOrder = config('xui.bundle_order', ['fi', 'nl']);

        $userinfos = [];

        try {
            $bundle = $this->bundleCollector->collect($nodes, $bundleOrder, $sub);

            foreach ($bundle['vless_entries'] as $entry) {
                if ($entry['userinfo'] !== []) {
                    $userinfos[$entry['key']] = $entry['userinfo'];
                }
            }

            $lines = SubscriptionExtraShareLines::orderedWithBundle($bundle, true);

            if ($lines === []) {
                throw new \RuntimeException('Ни один узел не отдал рабочую подписку.');
            }
        } catch (Throwable $e) {
            Log::warning('subscription.feed.error', [
                'message' => $e->getMessage(),
                'token_tail' => substr($sub->token, -6),
            ]);

            return new Response('Error: '.$e->getMessage(), 503, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Retry-After' => '30',
            ]);
        }

        $body = implode("\n", array_filter($lines))."\n";

        $quotaGb = (int) $sub->quota_gb;
        $totalCap = $quotaGb > 0 ? $quotaGb * self::BYTES_PER_GB : 0;
        $expireSec = (int) (($sub->expiry_ms ?? 0) / 1000);

        $up = array_sum(array_column($userinfos, 'upload'));
        $down = array_sum(array_column($userinfos, 'download'));
        $userinfo = $this->formatUserinfoValue($up, $down, $totalCap, $expireSec);

        $profileTitle = $this->profileTitleForHapp();
        $extras = HappSubscriptionAppManagementExtras::forResponses($sub, $up, $down);
        $meta = "#profile-title: {$profileTitle}\n#subscription-userinfo: {$userinfo}\n".$extras['body_meta_suffix'];

        $routingLine = HappRoutingSubscriptionLine::feedRoutingLine();

        if (config('xui.sub_output_b64', false)) {
            $encoded = base64_encode($meta.$body)."\n";
            $body = ($routingLine !== null ? $routingLine."\n" : '').$encoded;
        } else {
            $body = ($routingLine !== null ? $routingLine."\n" : '').$meta.$body;
        }

        $hours = (string) config('xui.sub_profile_update_hours', '12');

        $headers = array_merge([
            'Content-Type' => 'text/plain; charset=utf-8',
            'subscription-userinfo' => $userinfo,
            'profile-update-interval' => $hours,
        ], $extras['headers']);
        if (config('xui.feed_require_hwid', true)) {
            $headers['subscription-always-hwid-enable'] = '1';
        }
        if ($routingLine !== null) {
            $headers['routing'] = $routingLine;
        }

        return new Response($body, 200, $headers);
    }

    private function renderExpiredStubFeed(Subscription $sub): Response
    {
        $lines = ExpiredSubscriptionVlessStubs::lines();
        $body = implode("\n", array_filter($lines))."\n";

        $quotaGb = (int) $sub->quota_gb;
        $totalCap = $quotaGb > 0 ? $quotaGb * self::BYTES_PER_GB : 0;
        $expireSec = (int) (($sub->expiry_ms ?? 0) / 1000);
        $userinfo = $this->formatUserinfoValue(0, 0, $totalCap, $expireSec);

        $profileTitle = $this->profileTitleForHapp();
        $extras = HappSubscriptionAppManagementExtras::forResponses($sub, 0, 0);
        $meta = "#profile-title: {$profileTitle}\n#subscription-userinfo: {$userinfo}\n".$extras['body_meta_suffix'];

        $routingLine = HappRoutingSubscriptionLine::feedRoutingLine();

        if (config('xui.sub_output_b64', false)) {
            $encoded = base64_encode($meta.$body)."\n";
            $body = ($routingLine !== null ? $routingLine."\n" : '').$encoded;
        } else {
            $body = ($routingLine !== null ? $routingLine."\n" : '').$meta.$body;
        }

        $hours = (string) config('xui.sub_profile_update_hours', '12');

        $headers = array_merge([
            'Content-Type' => 'text/plain; charset=utf-8',
            'subscription-userinfo' => $userinfo,
            'profile-update-interval' => $hours,
        ], $extras['headers']);
        if (config('xui.feed_require_hwid', true)) {
            $headers['subscription-always-hwid-enable'] = '1';
        }
        if ($routingLine !== null) {
            $headers['routing'] = $routingLine;
        }

        return new Response($body, 200, $headers);
    }

    private function formatUserinfoValue(int $upload, int $download, int $total, int $expireSec): string
    {
        return "upload={$upload}; download={$download}; total={$total}; expire={$expireSec}";
    }

    private function profileTitleForHapp(): string
    {
        $raw = trim((string) config('xui.sub_profile_title', 'Nadezhda 🧭 VPN'));
        if ($raw === '') {
            return 'Nadezhda 🧭 VPN';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($raw, 0, 25);
        }

        return substr($raw, 0, 25);
    }
}
