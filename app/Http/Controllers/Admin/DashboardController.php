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
            'fi' => $subsCountFi,
            'nl' => $subsCountNl,
            'trial' => $trialActiveKeys,
        ];

        $ttl = max(10, config('links.metrics_cache_ttl', 20));
        $healthTtl = max(10, (int) config('links.health.cache_ttl', 30));
        $th = config('links.thresholds', []);

        // Для trial-связки «уникальные IP» по SSH на :443 включает сканеры/шум.
        // Поэтому для отображения «онлайн» считаем только активные test_keys через API панели.
        $trialOnlineTestClients = Cache::remember('trial_test_keys_online_clients_v1', $ttl, function (): ?int {
            $base = (string) config('test_keys.panel_base');
            $user = (string) config('test_keys.panel_username');
            $pass = (string) config('test_keys.panel_password');
            if ($base === '' || $user === '' || $pass === '') {
                return null;
            }

            $emails = TestKey::query()
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->limit(200)
                ->pluck('panel_email')
                ->filter()
                ->values()
                ->all();

            if ($emails === []) {
                return 0;
            }

            $client = new XuiPanelClient($base);
            $client->login($user, $pass);

            $online = array_flip($client->getOnlineClientEmails());
            $n = 0;
            foreach ($emails as $email) {
                if (isset($online[(string) $email])) {
                    $n++;
                }
            }

            return $n;
        });

        $bundles = collect(config('links.bundles', []))
            ->map(function (array $bundle) use ($subsPerBundle, $ttl, $healthTtl, $th, $trialOnlineTestClients) {
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

                if ($id === 'trial') {
                    $m = is_array($bundle['metrics']) ? $bundle['metrics'] : [];
                    if ($trialOnlineTestClients !== null) {
                        $m['trial_online_clients'] = $trialOnlineTestClients;
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

            return is_array($m) ? (int) ($m['unique_remote_ips'] ?? 0) : 0;
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
}
