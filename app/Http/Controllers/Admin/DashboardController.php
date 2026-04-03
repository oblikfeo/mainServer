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

        $bundles = collect(config('links.bundles', []))
            ->map(function (array $bundle) use ($keyCounts, $ttl) {
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
}
