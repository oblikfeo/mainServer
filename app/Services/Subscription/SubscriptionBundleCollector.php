<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Services\Hy2\BlitzClient;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

/**
 * Снимает с панелей текущие vless-линки по подписке (fi/nl/…) и опционально hy2:// — общая база URI и JSON-рендереров.
 */
final class SubscriptionBundleCollector
{
    /**
     * @param  array<string, array<string, mixed>>  $nodes
     * @param  list<string>  $bundleOrder
     * @return array{
     *   hy2_uri: ?string,
     *   vless_entries: list<array{key: string, line: string, userinfo: array<string, int>}>
     * }
     */
    public function collect(array $nodes, array $bundleOrder, Subscription $sub): array
    {
        $responses = $this->fetchPanelSubsParallel($nodes, $bundleOrder, $sub);

        $hy2Uri = null;
        if (config('hy2.enabled')) {
            $hy2User = (string) ($sub->hy2_username ?? '');
            $hy2Pass = (string) ($sub->hy2_password ?? '');
            if ($hy2User !== '' && $hy2Pass !== '') {
                $hy2Uri = BlitzClient::buildUri($hy2User, $hy2Pass);
            }
        }

        $entries = [];

        foreach ($bundleOrder as $key) {
            $node = $nodes[$key] ?? [];
            $resp = $responses[$key] ?? null;
            if ($resp === null) {
                continue;
            }

                $line = $this->extractProcessedVlessLine($resp, $node, strtoupper((string) $key), (string) $key);
            if ($line !== '') {
                $entries[] = [
                    'key' => $key,
                    'line' => $line,
                    'userinfo' => $this->parseUserinfoHeader($resp->header('subscription-userinfo')),
                ];
            }
        }

        return [
            'hy2_uri' => $hy2Uri,
            'vless_entries' => $entries,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $nodes
     * @param  list<string>  $bundleOrder
     * @return array<string, \Illuminate\Http\Client\Response>
     */
    private function fetchPanelSubsParallel(array $nodes, array $bundleOrder, Subscription $sub): array
    {
        $requests = [];

        foreach ($bundleOrder as $key) {
            $node = $nodes[$key] ?? [];
            $origin = rtrim((string) ($node['sub_origin'] ?? ''), '/');
            if ($origin === '') {
                continue;
            }

            $subIdField = $key.'_sub_id';
            $subId = (string) ($sub->$subIdField ?? '');
            if ($subId === '') {
                continue;
            }

            $requests[$key] = [
                'url' => $origin.'/sub/'.rawurlencode($subId),
                'headers' => $this->panelSubHeaders((string) ($node['pub_host'] ?? '')),
            ];
        }

        if ($requests === []) {
            throw new \RuntimeException('Нет настроенных узлов с sub_origin в .env');
        }

        $responses = Http::pool(function (Pool $pool) use ($requests) {
            $poolRequests = [];
            foreach ($requests as $key => $cfg) {
                $poolRequests[] = $pool->as($key)
                    ->withoutVerifying()
                    ->withHeaders($cfg['headers'])
                    ->connectTimeout(12)
                    ->timeout(28)
                    ->get($cfg['url']);
            }

            return $poolRequests;
        });

        return $responses;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function extractProcessedVlessLine(\Illuminate\Http\Client\Response $resp, array $node, string $label, string $bundleKey): string
    {
        if (! $resp->successful()) {
            return '';
        }
        $raw = trim($resp->body());
        if ($raw === '') {
            return '';
        }
        $line = VlessSubscriptionHelper::extractVlessLineFromSubscriptionBody($raw);
        if ($line === '' || ! str_starts_with($line, 'vless://')) {
            return '';
        }

        $pubHost = trim((string) ($node['pub_host'] ?? ''));
        if ($pubHost !== '' && (str_contains($line, '@127.0.0.1:') || str_contains($line, '@localhost:'))) {
            $line = VlessSubscriptionHelper::replaceVlessHost($line, $pubHost);
        }

        if ($pubHost !== '' && str_contains($line, 'security=tls')) {
            $line = VlessSubscriptionHelper::ensureTlsInsecure($line, $pubHost);
        }

        $line = VlessSubscriptionHelper::ensureRealitySid(
            $line,
            (string) ($node['reality_sid'] ?? '')
        );

        $serverDesc = SubscriptionHappSubtitle::forBundle($bundleKey);

        return VlessSubscriptionHelper::setVlessFragment(
            $line,
            (string) ($node['vless_display_name'] ?? $label),
            $serverDesc,
            (string) config('xui.vless_server_description_format', 'dual')
        );
    }

    /**
     * @return array<string, string>
     */
    private function panelSubHeaders(string $pubHost): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
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
            $k = trim($k);
            $v = trim($v);
            $out[$k] = (int) round((float) $v);
        }

        return $out;
    }
}
