<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\TestKey;
use App\Services\BundleHealthChecker;
use App\Services\BundleSshMetrics;
use App\Services\Xui\XuiPanelClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        protected BundleHealthChecker $bundleHealth,
        protected BundleSshMetrics $bundleSshMetrics
    ) {}

    public function index(): View
    {
        return view('admin.hub');
    }

    public function servers(): View
    {
        $nowMs = (int) (now()->getTimestamp() * 1000);

        $activeSubQ = Subscription::query()->where(function ($q) use ($nowMs) {
            $q->where('expiry_ms', '<=', 0)
                ->orWhere('expiry_ms', '>', $nowMs);
        });

        $subsCountWifi = (clone $activeSubQ)
            ->where(function ($q) {
                $q->whereNotNull('wifi_sub_id')->where('wifi_sub_id', '!=', '')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('wifi2_sub_id')->where('wifi2_sub_id', '!=', '');
                    });
            })
            ->count();
        $subsCountFi = (clone $activeSubQ)
            ->whereNotNull('fi_sub_id')
            ->where('fi_sub_id', '!=', '')
            ->count();
        $subsCountNl = (clone $activeSubQ)
            ->whereNotNull('nl_sub_id')
            ->where('nl_sub_id', '!=', '')
            ->count();

        $trialActiveKeys = TestKey::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();

        $subsPerBundle = [
            'wifi' => $subsCountWifi,
            'fi' => $subsCountFi,
            'nl' => $subsCountNl,
            'trial' => $trialActiveKeys,
        ];

        $ttl = max(10, config('links.metrics_cache_ttl', 20));
        $healthTtl = max(10, (int) config('links.health.cache_ttl', 30));
        $th = config('links.thresholds', []);

        // Онлайн и трафик для карточек берем из 3x-ui по активным подпискам/ключам,
        // чтобы не считать сканеры/шум через SSH на 443.
        $panelSnapshots = [
            'wifi' => Cache::remember('bundle_panel_snapshot_v4_wifi', $ttl, fn (): ?array => $this->buildPanelSnapshotForSubscriptionBundle('wifi')),
            'fi' => Cache::remember('bundle_panel_snapshot_v4_fi', $ttl, fn (): ?array => $this->buildPanelSnapshotForSubscriptionBundle('fi')),
            'nl' => Cache::remember('bundle_panel_snapshot_v4_nl', $ttl, fn (): ?array => $this->buildPanelSnapshotForSubscriptionBundle('nl')),
            'trial' => Cache::remember('bundle_panel_snapshot_v4_trial', $ttl, fn (): ?array => $this->buildPanelSnapshotForTrialBundle()),
        ];

        $bundles = collect(config('links.bundles', []))
            ->map(function (array $bundle) use ($subsPerBundle, $ttl, $healthTtl, $th, $panelSnapshots) {
                $id = $bundle['id'];

                $bundleForHealth = $bundle;
                $bundle['online'] = Cache::remember(
                    'bundle_health_v1_'.$id,
                    $healthTtl,
                    fn () => $this->bundleHealth->evaluateBundle($bundleForHealth)['online']
                );
                $bundle['subs_count'] = (int) ($subsPerBundle[$id] ?? 0);

                $bundleForSsh = $bundle;
                $bundle['metrics'] = Cache::remember(
                    'bundle_ssh_metrics_v8_'.$id,
                    $ttl,
                    fn () => $this->bundleSshMetrics->fetch($bundleForSsh)
                );

                if (isset($panelSnapshots[$id]) && is_array($panelSnapshots[$id])) {
                    $snapshot = $panelSnapshots[$id];
                    $m = is_array($bundle['metrics']) ? $bundle['metrics'] : [];
                    $m['panel_online_clients'] = (int) ($snapshot['online_clients'] ?? 0);
                    $m['traffic_total_bytes'] = $this->applyTrafficBaseline(
                        $id,
                        (int) ($snapshot['traffic_total_bytes'] ?? 0)
                    );
                    if ($id === 'trial') {
                        $m['trial_online_clients'] = (int) ($snapshot['online_clients'] ?? 0);
                    } else {
                        $m['unique_remote_ips'] = (int) ($snapshot['online_clients'] ?? 0);
                    }
                    $bundle['metrics'] = $m;
                }

                $capacity = max(1, (int) ($th['keys_capacity'] ?? 200));
                $warnR = (float) ($th['keys_warn_ratio'] ?? 0.65);
                $critR = (float) ($th['keys_crit_ratio'] ?? 0.90);
                $bundle['subs_level'] = $this->keysStressLevel($bundle['subs_count'], $capacity, $warnR, $critR);

                $m = $bundle['metrics'];
                $bundle['traffic_level'] = is_array($m)
                    ? $this->trafficStressLevel(
                        (int) ($m['traffic_total_bytes'] ?? 0),
                        (int) ($th['traffic_warn_bytes'] ?? 2_000_000_000_000),
                        (int) ($th['traffic_crit_bytes'] ?? 3_000_000_000_000)
                    )
                    : null;

                unset($bundle['ssh_private_key'], $bundle['ssh_user'], $bundle['client_tcp_port'], $bundle['ip']);

                return $bundle;
            })
            ->all();

        $onlineCount = collect($bundles)->where('online', true)->count();
        $totalBundles = count($bundles);
        $totalActiveSubs = Subscription::query()
            ->where(function ($q) use ($nowMs) {
                $q->where('expiry_ms', '<=', 0)
                    ->orWhere('expiry_ms', '>', $nowMs);
            })
            ->count();

        $totalConnections = (int) collect($bundles)->sum(function (array $b) {
            $m = $b['metrics'] ?? null;
            if (! is_array($m)) {
                return 0;
            }
            if (isset($m['panel_online_clients'])) {
                return (int) $m['panel_online_clients'];
            }
            if (($b['id'] ?? '') === 'trial') {
                return (int) ($m['trial_online_clients'] ?? 0);
            }

            return (int) ($m['unique_remote_ips'] ?? 0);
        });

        return view('admin.servers', [
            'bundles' => $bundles,
            'onlineCount' => $onlineCount,
            'totalBundles' => $totalBundles,
            'totalActiveSubs' => $totalActiveSubs,
            'totalConnections' => $totalConnections,
        ]);
    }

    private function keysStressLevel(int $count, int $capacity, float $warnRatio, float $critRatio): string
    {
        if ($count < $capacity * $warnRatio) {
            return 'ok';
        }
        if ($count < $capacity * $critRatio) {
            return 'warn';
        }

        return 'crit';
    }

    private function trafficStressLevel(int $bytes, int $warnBytes, int $critBytes): string
    {
        if ($bytes < $warnBytes) {
            return 'ok';
        }
        if ($bytes < $critBytes) {
            return 'warn';
        }

        return 'crit';
    }

    /**
     * @return array{online_clients: int, traffic_total_bytes: int}|null
     */
    private function buildPanelSnapshotForSubscriptionBundle(string $bundleKey): ?array
    {
        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');
        $node = config('xui.nodes.'.$bundleKey, []);
        $base = is_array($node) ? (string) ($node['panel_base'] ?? '') : '';
        $inboundId = is_array($node) ? (int) ($node['inbound_id'] ?? 0) : 0;
        if ($base === '' || $inboundId < 1 || $user === '' || $pass === '') {
            return null;
        }

        try {
            $client = new XuiPanelClient($base);
            $client->login($user, $pass);
            $allOnlineEmails = $client->getOnlineClientEmails();
            $online = count($allOnlineEmails);
            $onlineFlip = array_flip($allOnlineEmails);
            $traffic = 0;
            $inbound = $this->findInboundById($client->getInboundsList(), $inboundId);
            if ($inbound !== []) {
                [$subToEmail, $trafficByEmail] = $this->extractInboundMaps($inbound);
                $inboundEmails = array_values(array_unique(array_values($subToEmail)));
                $onlineEmailsInInbound = [];
                foreach ($inboundEmails as $email) {
                    if (isset($onlineFlip[$email])) {
                        $onlineEmailsInInbound[] = $email;
                    }
                }
                foreach ($onlineEmailsInInbound as $email) {
                    $traffic += (int) ($trafficByEmail[(string) $email] ?? 0);
                }
            }

            return [
                'online_clients' => $online,
                'traffic_total_bytes' => $traffic,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{online_clients: int, traffic_total_bytes: int}|null
     */
    private function buildPanelSnapshotForTrialBundle(): ?array
    {
        $base = (string) config('test_keys.panel_base');
        $user = (string) config('test_keys.panel_username');
        $pass = (string) config('test_keys.panel_password');
        $inboundId = (int) config('test_keys.inbound_id');
        if ($base === '' || $user === '' || $pass === '' || $inboundId < 1) {
            return null;
        }

        try {
            $client = new XuiPanelClient($base);
            $client->login($user, $pass);
            $allOnlineEmails = $client->getOnlineClientEmails();
            $online = count($allOnlineEmails);
            $onlineFlip = array_flip($allOnlineEmails);
            $traffic = 0;
            $inbound = $this->findInboundById($client->getInboundsList(), $inboundId);
            if ($inbound !== []) {
                [$subToEmail, $trafficByEmail] = $this->extractInboundMaps($inbound);
                $inboundEmails = array_values(array_unique(array_values($subToEmail)));
                $onlineEmailsInInbound = [];
                foreach ($inboundEmails as $email) {
                    if (isset($onlineFlip[$email])) {
                        $onlineEmailsInInbound[] = $email;
                    }
                }
                foreach ($onlineEmailsInInbound as $email) {
                    $traffic += (int) ($trafficByEmail[(string) $email] ?? 0);
                }
            }

            return [
                'online_clients' => $online,
                'traffic_total_bytes' => $traffic,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $inbound
     * @return array{0: array<string, string>, 1: array<string, int>}
     */
    private function extractInboundMaps(array $inbound): array
    {
        $settings = json_decode((string) ($inbound['settings'] ?? ''), true);
        if (! is_array($settings)) {
            return [[], []];
        }

        $clients = $settings['clients'] ?? [];
        if (! is_array($clients)) {
            return [[], []];
        }

        $subToEmail = [];
        foreach ($clients as $client) {
            if (! is_array($client)) {
                continue;
            }
            $subId = (string) ($client['subId'] ?? '');
            $email = (string) ($client['email'] ?? '');
            if ($subId !== '' && $email !== '') {
                $subToEmail[$subId] = $email;
            }
        }

        $trafficByEmail = [];
        $stats = $inbound['clientStats'] ?? [];
        if (is_array($stats)) {
            foreach ($stats as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $email = (string) ($row['email'] ?? '');
                if ($email === '') {
                    continue;
                }
                $trafficByEmail[$email] = (int) ($row['up'] ?? 0) + (int) ($row['down'] ?? 0);
            }
        }

        return [$subToEmail, $trafficByEmail];
    }

    /**
     * @param  list<array<string, mixed>>  $inbounds
     * @return array<string, mixed>
     */
    private function findInboundById(array $inbounds, int $inboundId): array
    {
        foreach ($inbounds as $row) {
            if (is_array($row) && (int) ($row['id'] ?? 0) === $inboundId) {
                return $row;
            }
        }

        return [];
    }

    private function applyTrafficBaseline(string $bundleId, int $panelBytes): int
    {
        $cfg = config('links.traffic_baseline.'.$bundleId, []);
        if (! is_array($cfg)) {
            return max(0, $panelBytes);
        }

        $displayBase = max(0, (int) ($cfg['display_bytes'] ?? 0));
        $panelBase = max(0, (int) ($cfg['panel_base_bytes'] ?? 0));
        if ($displayBase < 1) {
            return max(0, $panelBytes);
        }

        $delta = max(0, $panelBytes - $panelBase);

        return $displayBase + $delta;
    }

}
