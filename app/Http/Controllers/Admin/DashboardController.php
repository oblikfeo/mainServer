<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BundleHealthChecker;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected BundleHealthChecker $bundleHealth
    ) {}

    public function index(): View
    {
        $bundles = collect(config('links.bundles', []))
            ->map(function (array $bundle) {
                $port = (int) ($bundle['check_port'] ?? 22);
                $bundle['online'] = $this->bundleHealth->tcpReachable($bundle['ip'], $port);
                $bundle['check_port'] = $port;

                return $bundle;
            })
            ->all();

        $onlineCount = collect($bundles)->where('online', true)->count();

        return view('admin.dashboard', [
            'bundles' => $bundles,
            'onlineCount' => $onlineCount,
            'totalBundles' => count($bundles),
        ]);
    }
}
