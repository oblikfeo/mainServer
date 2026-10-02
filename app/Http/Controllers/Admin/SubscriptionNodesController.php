<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * «Узлы подписки»: какие кнопки сейчас отдаёт подписка (пулы SUB_AUTO_* из .env)
 * и какие серверы за ними стоят (справочник config/sub_nodes.php).
 */
class SubscriptionNodesController extends Controller
{
    public function index(): View
    {
        $pools = [
            'wifi' => $this->groupByServer($this->parsePool((array) config('xui.sub_auto.wifi', []))),
            'lte' => $this->groupByServer($this->parsePool((array) config('xui.sub_auto.lte', []))),
        ];

        return view('admin.subscription_nodes', [
            'pools' => $pools,
            'feedFormat' => (string) config('xui.sub_feed_format', 'uri'),
            'autoTitle' => (string) config('xui.sub_auto.remarks', ''),
            'wifiStrategy' => (string) config('xui.sub_auto.wifi_strategy', 'roundRobin'),
        ]);
    }

    /**
     * @param  array<int, string|null>  $uris
     * @return array<int, array<string, string|null>>
     */
    private function parsePool(array $uris): array
    {
        $directory = (array) config('sub_nodes', []);
        $rows = [];

        foreach (array_values(array_filter($uris)) as $i => $uri) {
            $node = $this->parseUri((string) $uri);
            $info = $directory[$node['host']] ?? [];

            $rows[] = $node + [
                'slot' => (string) ($i + 1),
                'provider' => $info['provider'] ?? null,
                'location' => $info['location'] ?? null,
                'server' => $info['server'] ?? null,
                'egress' => $info['egress'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Ссылки одного сервера (один хост) — в одну группу, порядок как в пуле.
     *
     * @param  array<int, array<string, string|null>>  $rows
     * @return array<int, array{host: string, provider: ?string, location: ?string, egress: ?string, links: array<int, array<string, string|null>>}>
     */
    private function groupByServer(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $host = (string) $row['host'];
            $groups[$host] ??= [
                'host' => $host,
                'provider' => $row['provider'],
                'location' => $row['location'],
                'egress' => $row['egress'],
                'links' => [],
            ];
            $groups[$host]['links'][] = $row;
        }

        return array_values($groups);
    }

    /**
     * @return array{name: string, host: string, port: string, protocol: string}
     */
    private function parseUri(string $uri): array
    {
        [$body, $fragment] = array_pad(explode('#', $uri, 2), 2, '');
        $name = rawurldecode($fragment);

        if (str_starts_with($body, 'vmess://')) {
            $json = json_decode((string) base64_decode(substr($body, 8)), true) ?: [];

            return [
                'name' => $name !== '' ? $name : (string) ($json['ps'] ?? ''),
                'host' => (string) ($json['add'] ?? ''),
                'port' => (string) ($json['port'] ?? ''),
                'protocol' => 'VMess · '.($json['net'] ?? 'tcp'),
            ];
        }

        $parts = parse_url($body) ?: [];
        parse_str((string) ($parts['query'] ?? ''), $q);

        $protocol = strtoupper((string) ($parts['scheme'] ?? '')).' · '.($q['type'] ?? 'tcp');
        if (($q['security'] ?? 'none') !== 'none') {
            $protocol .= ' · '.$q['security'];
        }
        if (! empty($q['flow'])) {
            $protocol .= ' · vision';
        }

        return [
            'name' => $name,
            'host' => (string) ($parts['host'] ?? ''),
            'port' => (string) ($parts['port'] ?? ''),
            'protocol' => $protocol,
        ];
    }
}
