<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Подписка «одна кнопка Авто»: JSON-массив из одного конфига Xray.
 *
 * Пул Wi-Fi: равномерно по живым узлам (roundRobin, мёртвые отсеивает observatory). Когда мёртв весь пул — fallback через loopback
 * в пул LTE (анти-глушилки). Российские домены — direct. Профиль маршрутизации Happ
 * должен быть выключен (HAPP_ROUTING_ENABLED=false → заголовок routing: happ://routing/off),
 * иначе Happ подменяет routing конфига и балансировщик не работает.
 */
final class AutoSubscriptionFeedRenderer
{
    private const BYTES_PER_GB = 1_073_741_824;

    public function __construct(
        private readonly SubscriptionBundleCollector $bundleCollector,
        private readonly MergedSubscriptionFeedRenderer $uriFeedRenderer,
    ) {}

    public function render(Subscription $sub): Response
    {
        if (ExpiredSubscriptionVlessStubs::shouldUse($sub)) {
            return $this->uriFeedRenderer->render($sub);
        }

        try {
            $doc = $this->buildConfig();
        } catch (Throwable $e) {
            Log::warning('subscription.auto.error', [
                'message' => $e->getMessage(),
                'token_tail' => substr($sub->token, -6),
            ]);

            return $this->uriFeedRenderer->render($sub);
        }

        [$up, $down] = $this->usage($sub);
        $quotaGb = (int) $sub->quota_gb;
        $totalCap = $quotaGb > 0 ? $quotaGb * self::BYTES_PER_GB : 0;
        $userinfo = SubscriptionFeedUserinfo::format(
            $up,
            $down,
            $totalCap,
            SubscriptionFeedUserinfo::expireUnixForSubscription($sub),
        );

        $extras = HappSubscriptionAppManagementExtras::forResponses($sub, $up, $down);
        $providerExtras = HappProviderIdSubscriptionExtras::forSubscriptionToken($sub->token);

        $headers = array_merge([
            'Content-Type' => 'application/json; charset=utf-8',
            'profile-title' => 'base64:'.base64_encode($this->profileTitle()),
            'subscription-userinfo' => $userinfo,
            'profile-update-interval' => (string) config('xui.sub_profile_update_hours', '1'),
        ], $extras['headers'], $providerExtras['headers']);
        if (config('xui.feed_require_hwid', true)) {
            $headers['subscription-always-hwid-enable'] = '1';
        }
        $routingLine = HappRoutingSubscriptionLine::feedRoutingLine();
        if ($routingLine !== null) {
            $headers['routing'] = $routingLine;
        }

        $body = json_encode([$doc], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new Response($body, 200, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildConfig(): array
    {
        $cfg = config('xui.sub_auto', []);
        $wifi = $this->convertPool((array) ($cfg['wifi'] ?? []), 'proxy-wifi-');
        $lte = $this->convertPool((array) ($cfg['lte'] ?? []), 'proxy-lte-');
        $wifiStrategy = ['type' => (string) ($cfg['wifi_strategy'] ?? 'roundRobin')];
        if ($wifi === [] && $lte === []) {
            throw new \RuntimeException('SUB_AUTO_WIFI_* и SUB_AUTO_LTE_* пусты или не разобрались.');
        }

        $outbounds = [...$wifi, ...$lte];
        $balancers = [];
        $rules = [];
        $subjects = [];

        if ($wifi !== [] && $lte !== []) {
            $outbounds[] = ['tag' => 'loopback-lte', 'protocol' => 'loopback', 'settings' => ['inboundTag' => 'lte-auto-reentry']];
            $balancers[] = ['tag' => 'wifi-pool', 'selector' => ['proxy-wifi-'], 'strategy' => $wifiStrategy, 'fallbackTag' => 'loopback-lte'];
            $balancers[] = ['tag' => 'lte-auto', 'selector' => ['proxy-lte-'], 'strategy' => ['type' => 'leastPing'], 'fallbackTag' => 'block'];
            $rules[] = ['type' => 'field', 'inboundTag' => ['lte-auto-reentry'], 'balancerTag' => 'lte-auto', 'ruleTag' => 'fallback-to-lte'];
            $defaultBalancer = 'wifi-pool';
            $subjects = ['proxy-wifi-', 'proxy-lte-'];
        } elseif ($wifi !== []) {
            $balancers[] = ['tag' => 'wifi-pool', 'selector' => ['proxy-wifi-'], 'strategy' => $wifiStrategy];
            $defaultBalancer = 'wifi-pool';
            $subjects = ['proxy-wifi-'];
        } else {
            $balancers[] = ['tag' => 'lte-auto', 'selector' => ['proxy-lte-'], 'strategy' => ['type' => 'leastPing']];
            $defaultBalancer = 'lte-auto';
            $subjects = ['proxy-lte-'];
        }

        $outbounds[] = ['tag' => 'direct', 'protocol' => 'freedom'];
        $outbounds[] = ['tag' => 'block', 'protocol' => 'blackhole'];

        $rules[] = ['type' => 'field', 'ip' => ['geoip:private'], 'outboundTag' => 'block'];
        $direct = array_values(array_filter((array) config('sub_auto_direct', [])));
        if ($direct !== []) {
            $rules[] = ['type' => 'field', 'domain' => $direct, 'outboundTag' => 'direct'];
        }
        $rules[] = ['type' => 'field', 'network' => 'tcp,udp', 'balancerTag' => $defaultBalancer, 'ruleTag' => 'default'];

        $sniffing = ['destOverride' => ['http', 'tls', 'quic'], 'enabled' => true, 'routeOnly' => true];

        return [
            'remarks' => (string) ($cfg['remarks'] ?? '🚀 Авто'),
            'log' => ['loglevel' => (string) ($cfg['log_level'] ?? 'warning')],
            'dns' => [
                'queryStrategy' => 'UseIPv4',
                'servers' => ['https://77.88.8.8/dns-query', '77.88.8.8', 'https://1.1.1.1/dns-query'],
            ],
            'inbounds' => [
                ['listen' => '127.0.0.1', 'port' => 10808, 'protocol' => 'socks', 'settings' => ['auth' => 'noauth', 'udp' => true], 'sniffing' => $sniffing, 'tag' => 'socks'],
                ['listen' => '127.0.0.1', 'port' => 10809, 'protocol' => 'http', 'settings' => ['allowTransparent' => false], 'sniffing' => $sniffing, 'tag' => 'http'],
            ],
            'outbounds' => $outbounds,
            'policy' => ['levels' => ['8' => ['bufferSize' => 3, 'connIdle' => 300, 'downlinkOnly' => 4, 'handshake' => 10, 'uplinkOnly' => 2]], 'system' => (object) []],
            'observatory' => [
                'subjectSelector' => $subjects,
                'probeURL' => 'https://www.google.com/generate_204',
                'probeInterval' => (string) ($cfg['probe_interval'] ?? '20s'),
                'enableConcurrency' => true,
            ],
            'routing' => ['domainStrategy' => 'AsIs', 'balancers' => $balancers, 'rules' => $rules],
        ];
    }

    /**
     * @param  list<string>  $uris
     * @return list<array<string, mixed>>
     */
    private function convertPool(array $uris, string $prefix): array
    {
        $out = [];
        $n = 1;
        foreach ($uris as $uri) {
            $ob = self::convertUri((string) $uri, $prefix.$n);
            if ($ob === null) {
                Log::warning('subscription.auto.convert_failed', ['prefix' => $prefix, 'uri_head' => substr((string) $uri, 0, 40)]);

                continue;
            }
            $out[] = $ob;
            $n++;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function convertUri(string $uri, string $tag): ?array
    {
        $uri = trim(explode('#', trim($uri), 2)[0]);
        if (str_starts_with($uri, 'vmess://')) {
            return self::convertVmess($uri, $tag);
        }
        if (! str_starts_with($uri, 'vless://')) {
            return null;
        }

        $p = parse_url($uri);
        if (! is_array($p) || empty($p['user']) || empty($p['host'])) {
            return null;
        }
        $q = [];
        parse_str(str_replace('+', '%2B', (string) ($p['query'] ?? '')), $q);

        $user = ['id' => (string) $p['user'], 'encryption' => 'none', 'level' => 8];
        if (! empty($q['flow'])) {
            $user['flow'] = (string) $q['flow'];
        }

        $net = strtolower((string) ($q['type'] ?? 'tcp'));
        $sec = strtolower((string) ($q['security'] ?? 'none'));
        $host = trim((string) $p['host'], '[]');
        $st = ['network' => $net, 'security' => $sec];

        if ($sec === 'reality') {
            $st['realitySettings'] = [
                'fingerprint' => (string) ($q['fp'] ?? 'chrome'),
                'serverName' => (string) ($q['sni'] ?? ''),
                'publicKey' => (string) ($q['pbk'] ?? ''),
                'shortId' => (string) ($q['sid'] ?? ''),
                'spiderX' => (string) ($q['spx'] ?? ''),
            ];
        } elseif ($sec === 'tls') {
            $st['tlsSettings'] = [
                'fingerprint' => (string) ($q['fp'] ?? 'chrome'),
                'serverName' => (string) ($q['sni'] ?? $host),
                'allowInsecure' => false,
            ];
        }

        switch ($net) {
            case 'tcp':
                $st['tcpSettings'] = ['header' => ['type' => 'none']];
                break;
            case 'grpc':
                $st['grpcSettings'] = ['serviceName' => (string) ($q['serviceName'] ?? ''), 'multiMode' => false];
                break;
            case 'ws':
                $st['wsSettings'] = ['path' => (string) ($q['path'] ?? '/')];
                break;
            case 'xhttp':
                $xs = [
                    'path' => (string) ($q['path'] ?? '/'),
                    'host' => (string) ($q['host'] ?? ''),
                    'mode' => (string) ($q['mode'] ?? 'auto'),
                ];
                $extra = json_decode((string) ($q['extra'] ?? ''), true);
                if (is_array($extra)) {
                    $xs['extra'] = $extra;
                }
                $st['xhttpSettings'] = $xs;
                break;
            default:
                return null;
        }

        return [
            'tag' => $tag,
            'protocol' => 'vless',
            'settings' => ['vnext' => [['address' => $host, 'port' => (int) ($p['port'] ?? 443), 'users' => [$user]]]],
            'streamSettings' => $st,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function convertVmess(string $uri, string $tag): ?array
    {
        $raw = base64_decode(substr($uri, 8).str_repeat('=', (4 - strlen(substr($uri, 8)) % 4) % 4), true);
        $vm = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($vm) || empty($vm['add']) || empty($vm['id'])) {
            return null;
        }
        if (($vm['net'] ?? 'tcp') !== 'ws') {
            return null;
        }

        return [
            'tag' => $tag,
            'protocol' => 'vmess',
            'settings' => ['vnext' => [[
                'address' => (string) $vm['add'],
                'port' => (int) $vm['port'],
                'users' => [['id' => (string) $vm['id'], 'alterId' => 0, 'security' => (string) ($vm['scy'] ?? 'auto'), 'level' => 8]],
            ]]],
            'streamSettings' => [
                'network' => 'ws',
                'security' => ($vm['tls'] ?? '') === 'tls' ? 'tls' : 'none',
                'wsSettings' => ['path' => (string) ($vm['path'] ?? '/')],
            ],
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function usage(Subscription $sub): array
    {
        try {
            $bundle = $this->bundleCollector->collect(
                config('xui.nodes', []),
                SubscriptionExtraShareLines::panelBundleOrder(),
                $sub,
            );
            $infos = array_filter(array_column($bundle['vless_entries'], 'userinfo'));

            return [(int) array_sum(array_column($infos, 'upload')), (int) array_sum(array_column($infos, 'download'))];
        } catch (Throwable) {
            return [0, 0];
        }
    }

    private function profileTitle(): string
    {
        $raw = trim((string) config('xui.sub_profile_title', 'Nadezhda 🧭 VPN'));

        return mb_substr($raw !== '' ? $raw : 'Nadezhda 🧭 VPN', 0, 25);
    }
}
