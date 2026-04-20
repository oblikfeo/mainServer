<?php

namespace App\Services\Subscription;

use App\Models\AppSetting;
use App\Models\Subscription;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class MergedSubscriptionFeedRenderer
{
    private const BYTES_PER_GB = 1_073_741_824;

    public function render(Subscription $sub): Response
    {
        $nodes = config('xui.nodes', []);
        $bundleOrder = config('xui.bundle_order', ['wifi', 'fi', 'nl']);

        $lines = [];
        $userinfos = [];

        try {
            $responses = $this->fetchPanelSubsParallel($nodes, $bundleOrder, $sub);

            foreach ($bundleOrder as $key) {
                $node = $nodes[$key] ?? [];
                $resp = $responses[$key] ?? null;
                if ($resp === null) {
                    continue;
                }

                $ok = $this->appendVlessLineIfOk($lines, $resp, $node, strtoupper($key));
                if ($ok) {
                    $userinfos[$key] = $this->parseUserinfoHeader($resp->header('subscription-userinfo'));
                }
            }

            if ($lines === []) {
                $statuses = [];
                foreach ($bundleOrder as $key) {
                    $resp = $responses[$key] ?? null;
                    $statuses[] = strtoupper($key).': '.($resp ? ($resp->successful() ? 'тело пусто' : 'HTTP '.$resp->status()) : 'нет ответа');
                }
                throw new \RuntimeException('Ни один узел не отдал рабочую подписку ('.implode(', ', $statuses).').');
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

        $quotaGb = max(1, (int) $sub->quota_gb);
        $totalCap = $quotaGb * self::BYTES_PER_GB;
        $expireSec = (int) (($sub->expiry_ms ?? 0) / 1000);
        if ($expireSec === 0) {
            $expireSec = max(array_column($userinfos, 'expire'));
        }

        $up = array_sum(array_column($userinfos, 'upload'));
        $down = array_sum(array_column($userinfos, 'download'));
        $userinfo = $this->formatUserinfoValue($up, $down, $totalCap, $expireSec);

        $profileTitle = $this->profileTitleForHapp();
        $meta = "#profile-title: {$profileTitle}\n#subscription-userinfo: {$userinfo}\n";

        $routingLine = $this->happRoutingLineForBody();

        if (config('xui.sub_output_b64', false)) {
            $encoded = base64_encode($meta.$body)."\n";
            $body = ($routingLine !== null ? $routingLine."\n" : '').$encoded;
        } else {
            $body = ($routingLine !== null ? $routingLine."\n" : '').$meta.$body;
        }

        $hours = (string) config('xui.sub_profile_update_hours', '12');

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

        // Имя профиля только в теле (#profile-title) — в HTTP-заголовке UTF-8/прокси часто ломают ответ.
        return new Response($body, 200, $headers);
    }

    /**
     * Параллельно запрашиваем все ноды из bundle_order.
     *
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
     * @param  list<string>  $lines
     * @param  array<string, mixed>  $node
     */
    private function appendVlessLineIfOk(array &$lines, \Illuminate\Http\Client\Response $resp, array $node, string $label): bool
    {
        if (! $resp->successful()) {
            return false;
        }
        $raw = trim($resp->body());
        if ($raw === '') {
            return false;
        }
        $line = VlessSubscriptionHelper::extractVlessLineFromSubscriptionBody($raw);
        if ($line === '' || ! str_starts_with($line, 'vless://')) {
            return false;
        }

        // Заменяем localhost / 127.0.0.1 на публичный хост из конфига
        $pubHost = trim((string) ($node['pub_host'] ?? ''));
        if ($pubHost !== '' && (str_contains($line, '@127.0.0.1:') || str_contains($line, '@localhost:'))) {
            $line = VlessSubscriptionHelper::replaceVlessHost($line, $pubHost);
        }

        // Для TLS с самоподписанным сертификатом добавляем allowInsecure и sni
        if ($pubHost !== '' && str_contains($line, 'security=tls')) {
            $line = VlessSubscriptionHelper::ensureTlsInsecure($line, $pubHost);
        }

        $serverDesc = (string) ($node['vless_server_description'] ?? config('xui.vless_server_description', ''));

        $lines[] = VlessSubscriptionHelper::setVlessFragment(
            $line,
            (string) ($node['vless_display_name'] ?? $label),
            $serverDesc,
            (string) config('xui.vless_server_description_format', 'dual')
        );

        return true;
    }

    /**
     * Пустые X-Forwarded-* ломают часть инсталляций 3x-ui/nginx; не шлём, если pub_host не задан.
     *
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
        $fromDb = null;
        try {
            $fromDb = AppSetting::getValue('happ_profile_title');
        } catch (Throwable) {
        }

        $raw = trim((string) ($fromDb !== null && $fromDb !== '' ? $fromDb : config('xui.sub_profile_title', 'nadezhda VPN')));
        if ($raw === '') {
            return 'nadezhda VPN';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($raw, 0, 25);
        }

        return substr($raw, 0, 25);
    }
}
