<?php

namespace App\Services\Subscription;

use App\Models\AppSetting;
use App\Models\TestKey;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class TestKeySubscriptionFeedRenderer
{
    private const BYTES_PER_GB = 1_073_741_824;

    public function render(TestKey $key): Response
    {
        $line = '';
        $ui = [];
        $subId = trim((string) $key->panel_sub_id);
        if ($subId !== '') {
            $origin = trim((string) config('test_keys.sub_origin'));
            if ($origin === '') {
                $panelBase = trim((string) config('test_keys.panel_base'));
                if ($panelBase !== '') {
                    $origin = rtrim($panelBase, '/');
                }
            }

            if ($origin !== '') {
                $url = rtrim($origin, '/').'/sub/'.rawurlencode($subId);
                try {
                    $resp = Http::withoutVerifying()
                        ->withHeaders($this->panelSubHeaders((string) config('test_keys.pub_host')))
                        ->connectTimeout(12)
                        ->timeout(28)
                        ->get($url);

                    if ($resp->successful()) {
                        $raw = trim($resp->body());
                        $line = VlessSubscriptionHelper::extractVlessLineFromSubscriptionBody($raw);
                        $ui = $this->parseUserinfoHeader($resp->header('subscription-userinfo'));
                    }
                } catch (Throwable) {
                    // На части инсталляций trial-панель не открывает /sub/{subId}: используем локальный fallback.
                }
            }
        }

        if ($line === '') {
            $line = trim((string) $key->vless_url);
        }
        if ($line === '' || ! str_starts_with($line, 'vless://')) {
            return new Response('Error: test key has invalid vless link', 503, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $line = VlessSubscriptionHelper::setVlessFragment(
            $line,
            (string) config('test_keys.vless_display_name', 'Trial'),
            (string) config('xui.vless_server_description', ''),
            (string) config('xui.vless_server_description_format', 'dual')
        );

        $up = (int) ($ui['upload'] ?? 0);
        $down = (int) ($ui['download'] ?? 0);
        $total = (int) ($ui['total'] ?? max(1, (int) $key->quota_gb) * self::BYTES_PER_GB);
        $expireSec = (int) ($ui['expire'] ?? ($key->expires_at?->getTimestamp() ?? 0));
        $userinfo = $this->formatUserinfoValue($up, $down, $total, $expireSec);

        $profileTitle = $this->profileTitleForHapp();
        $meta = "#profile-title: {$profileTitle}\n#subscription-userinfo: {$userinfo}\n";
        $routingLine = $this->happRoutingLineForBody();

        $body = $meta.$line."\n";
        if (config('xui.sub_output_b64', false)) {
            $encoded = base64_encode($body)."\n";
            $body = ($routingLine !== null ? $routingLine."\n" : '').$encoded;
        } else {
            $body = ($routingLine !== null ? $routingLine."\n" : '').$body;
        }

        $hours = (string) config('test_keys.default_hours', '8');
        $headers = [
            'Content-Type' => 'text/plain; charset=utf-8',
            'subscription-userinfo' => $userinfo,
            'profile-update-interval' => $hours,
        ];
        if (config('xui.feed_require_hwid', true)) {
            $headers['subscription-always-hwid-enable'] = '1';
        }
        if ($routingLine !== null) {
            $headers['routing'] = $routingLine;
        }

        return new Response($body, 200, $headers);
    }

    /**
     * @return array<string, string>
     */
    private function panelSubHeaders(string $pubHost): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0',
            'Accept' => '*/*',
            'Accept-Encoding' => 'identity',
        ];
        $pubHost = trim($pubHost);
        if ($pubHost !== '') {
            $headers['X-Forwarded-Host'] = $pubHost;
            $headers['X-Real-IP'] = $pubHost;
        }

        return $headers;
    }

    /**
     * @return array<string, int>
     */
    private function parseUserinfoHeader(?string $val): array
    {
        if ($val === null || $val === '') {
            return [];
        }
        $out = [];
        foreach (explode(';', $val) as $part) {
            $part = trim($part);
            if (! str_contains($part, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $part, 2);
            $out[trim($k)] = (int) round((float) trim($v));
        }

        return $out;
    }

    private function formatUserinfoValue(int $upload, int $download, int $total, int $expireSec): string
    {
        return "upload={$upload}; download={$download}; total={$total}; expire={$expireSec}";
    }

    private function happRoutingLineForBody(): ?string
    {
        $cfg = config('xui.happ_routing', []);
        if (! is_array($cfg) || ! filter_var($cfg['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return null;
        }

        $name = trim((string) ($cfg['profile_name'] ?? 'direct'));
        if ($name === '') {
            $name = 'direct';
        }

        $configSites = $cfg['direct_sites'] ?? [];
        if (! is_array($configSites)) {
            $configSites = [];
        }

        $adminRaw = '';
        try {
            $adminRaw = AppSetting::getValue('happ_routing_rules') ?? '';
        } catch (Throwable) {
        }

        $parsed = HappRoutingRulesParser::parse((string) $adminRaw);
        $sites = $this->mergeUniqueRoutingTokens($configSites, $parsed['sites']);
        $onAdd = filter_var($cfg['onadd'] ?? true, FILTER_VALIDATE_BOOL);

        return HappRoutingSubscriptionLine::buildOnAddLine($name, $sites, $onAdd, $parsed['ips']);
    }

    /**
     * @param  list<string>|array<int|string, mixed>  $base
     * @param  list<string>  $extra
     * @return list<string>
     */
    private function mergeUniqueRoutingTokens(array $base, array $extra): array
    {
        $seen = [];
        $out = [];
        foreach ([...$base, ...$extra] as $s) {
            $s = trim((string) $s);
            if ($s === '') {
                continue;
            }
            $k = strtolower($s);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $s;
        }

        return $out;
    }

    private function profileTitleForHapp(): string
    {
        $raw = trim((string) config('xui.sub_profile_title', 'nadezhda VPN'));
        if ($raw === '') {
            return 'nadezhda VPN';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($raw, 0, 25);
        }

        return substr($raw, 0, 25);
    }
}

