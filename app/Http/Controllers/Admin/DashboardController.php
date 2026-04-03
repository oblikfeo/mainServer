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

    /**
     * Главная админки: плитки разделов.
     */
    public function index(): View
    {
        return view('admin.hub');
    }

    /**
     * Статус связок: реальная TCP-проверка с этого сервера до IP:порт из config/links.php.
     */
    public function servers(): View
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

        return view('admin.servers', [
            'bundles' => $bundles,
            'onlineCount' => $onlineCount,
            'totalBundles' => count($bundles),
        ]);
    }
}
