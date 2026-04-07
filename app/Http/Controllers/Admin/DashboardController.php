<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssuedKey;
use App\Models\Subscription;
use App\Services\BundleHealthChecker;
use App\Services\BundleSshMetrics;
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

        $subsPerBundle = IssuedKey::query()
            ->join('subscriptions', 'subscriptions.id', '=', 'issued_keys.subscription_id')
            ->where(function ($q) use ($nowMs) {
                $q->where('subscriptions.expiry_ms', '<=', 0)
                    ->orWhere('subscriptions.expiry_ms', '>', $nowMs);
            })
            ->selectRaw('issued_keys.bundle_id, COUNT(DISTINCT issued_keys.subscription_id) as c')
            ->groupBy('issued_keys.bundle_id')
            ->pluck('c', 'bundle_id');

        $ttl = max(15, config('links.metrics_cache_ttl', 45));
        $healthTtl = max(10, (int) config('links.health.cache_ttl', 30));
        $th = config('links.thresholds', []);

        $bundles = collect(config('links.bundles', []))
            ->map(function (array $bundle) use ($subsPerBundle, $ttl, $healthTtl, $th) {
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
                    'bundle_ssh_metrics_v4_'.$id,
                    $ttl,
                    fn () => $this->bundleSshMetrics->fetch($bundleForSsh)
                );

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
        $totalActiveSubs = Subscription::query()
            ->where(function ($q) use ($nowMs) {
                $q->where('expiry_ms', '<=', 0)
                    ->orWhere('expiry_ms', '>', $nowMs);
            })
            ->count();

        return view('admin.servers', [
            'bundles' => $bundles,
            'onlineCount' => $onlineCount,
            'totalBundles' => count($bundles),
            'totalActiveSubs' => $totalActiveSubs,
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
