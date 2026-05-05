<?php

namespace App\Services\Subscription;

use App\Models\AppSetting;
use App\Models\Subscription;
use App\Models\TestKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Подписка Happ: JSON конфиг Xray (per_node по умолчанию или merged + balancer).
 * Откат: SUB_FEED_FORMAT=uri в .env
 */
final class XrayJsonSubscriptionFeedRenderer
{
    private const BYTES_PER_GB = 1_073_741_824;

    private const BALANCER_TAG = 'nadezhda-bal';

    public function __construct(
        private readonly SubscriptionBundleCollector $bundleCollector,
    ) {}

    public function render(Subscription $sub): Response
    {
        $nodes = config('xui.nodes', []);
        $bundleOrder = config('xui.bundle_order', ['fi', 'nl']);

        try {
            $bundleMode = strtolower(trim((string) config('xui.sub_json_bundle_mode', 'per_node')));

            $bundle = $this->bundleCollector->collect($nodes, $bundleOrder, $sub);

            $convertedRows = [];
            foreach ($bundle['vless_entries'] as $idx => $entry) {
                $stripped = explode('#', $entry['line'], 2)[0];
                $tag = $bundleMode === 'merged'
                    ? ('proxy-'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $entry['key']).'-'.$idx)
                    : 'proxy';
                $ob = VlessUriToXrayOutbound::convert($stripped, $tag);
                if ($ob === null) {
                    Log::warning('subscription.json.vless_convert_failed', [
                        'bundle' => $entry['key'],
                        'token_tail' => substr($sub->token, -6),
                    ]);

                    continue;
                }
                $convertedRows[] = ['entry' => $entry, 'ob' => $ob];
            }

            if ($convertedRows === []) {
                throw new \RuntimeException('Не удалось собрать ни одного VLESS outbound в JSON.');
            }

            $quotaGb = (int) $sub->quota_gb;
            $totalCap = $quotaGb > 0 ? $quotaGb * self::BYTES_PER_GB : 0;
            $expireSec = (int) (($sub->expiry_ms ?? 0) / 1000);

            $userinfos = [];
            foreach ($bundle['vless_entries'] as $entry) {
                if ($entry['userinfo'] !== []) {
                    $userinfos[$entry['key']] = $entry['userinfo'];
                }
            }
            $up = array_sum(array_column($userinfos, 'upload'));
            $down = array_sum(array_column($userinfos, 'download'));
            $userinfo = $this->formatUserinfoValue($up, $down, $totalCap, $expireSec);

            $profileTitle = $this->profileTitleForHapp();
            $extras = HappSubscriptionAppManagementExtras::forResponses($sub);

            $globalMetaOverride = trim((string) config('xui.sub_json_meta_server_description', ''));

            if ($bundleMode === 'merged') {
                $forMeta = [];
                foreach ($convertedRows as $row) {
                    $forMeta[] = $row['entry'];
                }
                $metaDesc = $this->composeMetaServerDescription($nodes, $forMeta);
                if ($globalMetaOverride !== '') {
                    $metaDesc = $globalMetaOverride;
                }
                $mergedOutbounds = array_map(static fn (array $row): mixed => $row['ob'], $convertedRows);

                $doc = $this->buildXrayDoc(
                    $mergedOutbounds,
                    $profileTitle,
                    $metaDesc,
                );

                $jsonBlob = $this->encodeJsonDocument(
                    $doc,
                    filter_var(config('xui.sub_json_pretty_print', false), FILTER_VALIDATE_BOOL),
                );
            } else {
                $jsonPieces = [];
                foreach ($convertedRows as $row) {
                    /** @var array{key:string,line:string,userinfo:array<string,int>} $entry */
                    $entry = $row['entry'];

                    $node = $nodes[$entry['key']] ?? [];
                    $remarks = trim((string) ($node['vless_display_name'] ?? strtoupper((string) $entry['key'])));
                    $remarks = $this->shortenHappLabel($remarks, 64);

                    $metaDesc = SubscriptionHappSubtitle::forBundle((string) $entry['key']);
                    if ($globalMetaOverride !== '') {
                        $metaDesc = $globalMetaOverride;
                    }

                    $doc = $this->buildXrayDoc([$row['ob']], $remarks, $metaDesc);
                    $jsonPieces[] = $this->encodeJsonDocument($doc, false);
                }
                $jsonBlob = implode("\n", $jsonPieces);
            }

            $routingLine = $this->happRoutingLineForBody();
            $meta = "#profile-title: {$profileTitle}\n#subscription-userinfo: {$userinfo}\n".$extras['body_meta_suffix'];

            $bodySuffix = '';
            if (filter_var(config('xui.sub_json_append_hy2_uri', true), FILTER_VALIDATE_BOOL) && $bundle['hy2_uri'] !== null && $bundle['hy2_uri'] !== '') {
                $bodySuffix = "\n".trim((string) $bundle['hy2_uri'])."\n";
            }

            $coreBody = $meta."\n".$jsonBlob.$bodySuffix;

            if (config('xui.sub_output_b64', false)) {
                $encoded = base64_encode($coreBody)."\n";
                $body = ($routingLine !== null ? $routingLine."\n" : '').$encoded;
            } else {
                $body = ($routingLine !== null ? $routingLine."\n" : '').$coreBody;
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
        } catch (Throwable $e) {
            Log::warning('subscription.json.feed.error', [
                'message' => $e->getMessage(),
                'token_tail' => substr($sub->token, -6),
            ]);

            return new Response('Error: '.$e->getMessage(), 503, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Retry-After' => '30',
            ]);
        }
    }

    public function renderTestKey(TestKey $key): Response
    {
        $payload = $this->fetchTestKeyPayload($key);

        if ($payload['line'] === '') {
            return new Response('Error: test key has invalid vless link', 503, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        try {
            $stripped = explode('#', $payload['line'], 2)[0];
            $ob = VlessUriToXrayOutbound::convert($stripped, 'proxy');
            if ($ob === null) {
                throw new \RuntimeException('Не удалось разобрать trial vless для JSON.');
            }

            $up = (int) ($payload['userinfo']['upload'] ?? 0);
            $down = (int) ($payload['userinfo']['download'] ?? 0);
            $total = (int) ($payload['userinfo']['total'] ?? max(1, (int) $key->quota_gb) * self::BYTES_PER_GB);
            $expireSec = (int) ($payload['userinfo']['expire'] ?? ($key->expires_at?->getTimestamp() ?? 0));
            $userinfo = $this->formatUserinfoValue($up, $down, $total, $expireSec);

            $profileTitle = $this->profileTitleForHapp();
            $extras = HappSubscriptionAppManagementExtras::forResponses($key);

            $trialRemarks = $this->shortenHappLabel(trim((string) config('test_keys.vless_display_name', 'Trial')), 64);
            $metaDesc = SubscriptionHappSubtitle::forTestKey();

            $jsonPretty = $this->encodeJsonDocument(
                $doc,
                filter_var(config('xui.sub_json_pretty_print', false), FILTER_VALIDATE_BOOL),
            );

            $routingLine = config('test_keys.apply_happ_routing', false) ? $this->happRoutingLineForBody() : null;
            $meta = "#profile-title: {$profileTitle}\n#subscription-userinfo: {$userinfo}\n".$extras['body_meta_suffix'];
            $coreBody = $meta."\n".$jsonPretty."\n";

            if (config('xui.sub_output_b64', false)) {
                $encoded = base64_encode($coreBody)."\n";
                $body = ($routingLine !== null ? $routingLine."\n" : '').$encoded;
            } else {
                $body = ($routingLine !== null ? $routingLine."\n" : '').$coreBody;
            }

            $hours = (string) config('test_keys.default_hours', '8');
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
        } catch (Throwable $e) {
            Log::warning('subscription.json.test.error', ['message' => $e->getMessage()]);

            return new Response('Error: '.$e->getMessage(), 503, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }
    }

    /**
     * @return array{line: string, userinfo: array<string, int>}
     */
    private function fetchTestKeyPayload(TestKey $key): array
    {
        $line = '';
        $userinfo = [];
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
                        $userinfo = $this->parseUserinfoHeader($resp->header('subscription-userinfo'));
                    }
                } catch (Throwable) {
                    // fallback ниже на vless_url
                }
            }
        }

        if ($line === '') {
            $line = trim((string) $key->vless_url);
        }

        if ($line === '' || ! str_starts_with($line, 'vless://')) {
            return ['line' => '', 'userinfo' => []];
        }

        return [
            'line' => VlessSubscriptionHelper::setVlessFragment(
                $line,
                (string) config('test_keys.vless_display_name', 'Trial'),
                SubscriptionHappSubtitle::forTestKey(),
                (string) config('xui.vless_server_description_format', 'dual')
            ),
            'userinfo' => $userinfo,
        ];
    }

    private function encodeJsonDocument(array $doc, bool $pretty): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($doc, $flags);
    }

    private function shortenHappLabel(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }
        if (function_exists('mb_strlen') && mb_strlen($value) > $max) {
            return mb_substr($value, 0, $max);
        }
        if (strlen($value) > $max) {
            return substr($value, 0, $max);
        }

        return $value;
    }

    /**
     * @param  list<array<string, mixed>>  $proxyOutbounds
     * @return array<string, mixed>
     */
    private function buildXrayDoc(array $proxyOutbounds, string $remarks, string $metaServerDescription): array
    {
        $proxyTags = [];
        foreach ($proxyOutbounds as $ob) {
            if (isset($ob['tag']) && $ob['tag'] !== '') {
                $proxyTags[] = (string) $ob['tag'];
            }
        }

        $routing = count($proxyTags) > 1 ? $this->routingWithBalancer($proxyTags) : $this->routingSingle($proxyTags[0] ?? 'proxy');

        $doc = [
            'dns' => [
                'queryStrategy' => 'UseIPv4',
                'servers' => ['8.8.8.8', '9.9.9.9'],
            ],
            'log' => [
                'loglevel' => 'warning',
            ],
            'meta' => [
                'serverDescription' => $metaServerDescription,
            ],
            'remarks' => $remarks,
            'inbounds' => [
                [
                    'listen' => '127.0.0.1',
                    'port' => 10808,
                    'protocol' => 'socks',
                    'settings' => [
                        'auth' => 'noauth',
                        'udp' => true,
                    ],
                    'sniffing' => [
                        'enabled' => true,
                        'destOverride' => ['http', 'tls', 'quic'],
                    ],
                    'tag' => 'socks',
                ],
                [
                    'listen' => '127.0.0.1',
                    'port' => 10809,
                    'protocol' => 'http',
                    'settings' => [
                        'allowTransparent' => false,
                    ],
                    'sniffing' => [
                        'enabled' => true,
                        'destOverride' => ['http', 'tls', 'quic'],
                    ],
                    'tag' => 'http',
                ],
            ],
            'routing' => $routing,
            'outbounds' => [
                ...$proxyOutbounds,
                [
                    'protocol' => 'freedom',
                    'tag' => 'direct',
                ],
                [
                    'protocol' => 'blackhole',
                    'tag' => 'block',
                ],
            ],
        ];

        return $doc;
    }

    /**
     * @param  list<string>  $proxyTags
     * @return array<string, mixed>
     */
    private function routingWithBalancer(array $proxyTags): array
    {
        $rules = $this->commonDirectRules();
        $rules[] = [
            'type' => 'field',
            'network' => 'tcp,udp',
            'balancerTag' => self::BALANCER_TAG,
        ];

        return [
            'domainStrategy' => 'AsIs',
            'balancers' => [
                [
                    'tag' => self::BALANCER_TAG,
                    'selector' => array_values(array_unique($proxyTags)),
                    'strategy' => [
                        'type' => 'random',
                    ],
                ],
            ],
            'rules' => $rules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routingSingle(string $proxyTag): array
    {
        $rules = $this->commonDirectRules();
        $rules[] = [
            'type' => 'field',
            'network' => 'tcp,udp',
            'outboundTag' => $proxyTag,
        ];

        return [
            'domainStrategy' => 'AsIs',
            'rules' => $rules,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function commonDirectRules(): array
    {
        $domainsConfig = config('xui.sub_json_direct_domains');
        if (is_array($domainsConfig) && $domainsConfig !== []) {
            /** @phpstan-ignore-next-line */
            return array_values($domainsConfig);
        }

        return $this->defaultDirectDomainsPreset();
    }

    /**
     * Список domain: совпадает с образцом конкурентов (локальный direct).
     *
     * @return list<array<string, mixed>>
     */
    private function defaultDirectDomainsPreset(): array
    {
        return [
            ['outboundTag' => 'direct', 'protocol' => ['bittorrent']],
            [
                'outboundTag' => 'direct',
                'domain' => [
                    'domain:mtalk.google.com',
                    'domain:push.apple.com',
                    'domain:api.push.apple.com',
                    'domain:push-apple.com.akadns.net',
                    'domain:courier.push.apple.com',
                    'domain:mangabuff.ru',
                    'domain:yandex.com',
                    'domain:yandex.net',
                    'domain:mail.ru',
                    'domain:vk.com',
                    'domain:vkusvill.ru',
                    'domain:ozon.ru',
                    'domain:wildberries.ru',
                    'domain:tinkoff.ru',
                    'domain:gosuslugi.ru',
                    'domain:nalog.gov.ru',
                    'domain:mos.ru',
                    'domain:2gis.com',
                    'domain:2gis.ru',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $nodes
     * @param  list<array{key:string,line:string,userinfo:array<string,int>}>  $entries
     */
    private function composeMetaServerDescription(array $nodes, array $entries): string
    {
        $parts = [];
        foreach ($entries as $entry) {
            $node = $nodes[$entry['key']] ?? [];
            $name = trim((string) ($node['vless_display_name'] ?? strtoupper((string) $entry['key'])));
            $desc = SubscriptionHappSubtitle::forBundle((string) $entry['key']);
            if ($desc !== '') {
                $parts[] = $name.' — '.$desc;
            }
        }
        $combined = implode(' · ', array_unique(array_filter($parts)));
        if ($combined !== '') {
            return $combined;
        }

        return trim((string) config('xui.vless_server_description', ''));
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
}
