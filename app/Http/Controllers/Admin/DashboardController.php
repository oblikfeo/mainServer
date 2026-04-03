<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssuedKey;
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
        $keyCounts = IssuedKey::query()
            ->selectRaw('bundle_id, COUNT(*) as c')
            ->groupBy('bundle_id')
            ->pluck('c', 'bundle_id');

        $ttl = max(15, config('links.metrics_cache_ttl', 45));
        $th = config('links.thresholds', []);

        $bundles = collect(config('links.bundles', []))
            ->map(function (array $bundle) use ($keyCounts, $ttl, $th) {
                $port = (int) ($bundle['check_port'] ?? 22);
                $id = $bundle['id'];

                $bundle['online'] = $this->bundleHealth->tcpReachable($bundle['ip'], $port);
                $bundle['keys_count'] = (int) ($keyCounts[$id] ?? 0);

                $bundleForSsh = $bundle;
                $bundle['metrics'] = Cache::remember(
                    'bundle_ssh_metrics_v2_'.$id,
                    $ttl,
                    fn () => $this->bundleSshMetrics->fetch($bundleForSsh)
                );

                $capacity = max(1, (int) ($th['keys_capacity'] ?? 200));
                $warnR = (float) ($th['keys_warn_ratio'] ?? 0.65);
                $critR = (float) ($th['keys_crit_ratio'] ?? 0.90);
                $bundle['keys_level'] = $this->keysStressLevel($bundle['keys_count'], $capacity, $warnR, $critR);

                $m = $bundle['metrics'];
                $bundle['traffic_level'] = is_array($m)
                    ? $this->trafficStressLevel(
                        (int) ($m['traffic_total_bytes'] ?? 0),
                        (int) ($th['traffic_warn_bytes'] ?? 2_000_000_000_000),
                        (int) ($th['traffic_crit_bytes'] ?? 3_000_000_000_000)
                    )
                    : null;

                unset($bundle['ssh_private_key'], $bundle['ssh_user'], $bundle['check_port'], $bundle['ip']);

                return $bundle;
            })
            ->all();

        $onlineCount = collect($bundles)->where('online', true)->count();
        $totalKeys = IssuedKey::query()->count();

        return view('admin.servers', [
            'bundles' => $bundles,
            'onlineCount' => $onlineCount,
            'totalBundles' => count($bundles),
            'totalKeys' => $totalKeys,
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
