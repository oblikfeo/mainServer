<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Xui\XuiSubscriptionConnectionInspector;
use App\Services\Xui\XuiSubscriptionTrafficMaps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, XuiSubscriptionTrafficMaps $trafficMaps, XuiSubscriptionConnectionInspector $connectionInspector): View
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $q = Subscription::query()->with('user')->orderByDesc('created_at');

        if (is_string($dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $q->whereDate('created_at', '>=', $dateFrom);
        }
        if (is_string($dateTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $q->whereDate('created_at', '<=', $dateTo);
        }

        $subscriptions = $q->paginate(20)->withQueryString();

        $ttl = max(15, (int) config('xui.report_traffic_cache_ttl', 60));
        $payload = Cache::remember('admin_report_xui_traffic_v1', $ttl, fn () => $trafficMaps->fetch());

        $connTtl = max(15, (int) config('xui.report_connection_cache_ttl', 60));
        $connectionPayload = Cache::remember(
            'admin_report_subscription_connections_v1',
            $connTtl,
            fn () => $connectionInspector->inspectAllActive()
        );

        return view('admin.report', [
            'subscriptions' => $subscriptions,
            'trafficMaps' => $payload['maps'] ?? [],
            'trafficErrors' => $payload['errors'] ?? [],
            'connectionBySubId' => $connectionPayload['by_subscription_id'] ?? [],
            'connectionErrors' => $connectionPayload['errors'] ?? [],
            'dateFrom' => is_string($dateFrom) ? $dateFrom : '',
            'dateTo' => is_string($dateTo) ? $dateTo : '',
            'byteFmt' => \Closure::fromCallable([$this, 'formatBytes']),
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        $bytes = max(0, $bytes);
        $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        $i = 0;
        $v = (float) $bytes;
        while ($v >= 1024 && $i < count($units) - 1) {
            $v /= 1024;
            $i++;
        }

        return $i === 0
            ? sprintf('%d %s', $bytes, $units[$i])
            : sprintf('%.2f %s', $v, $units[$i]);
    }
}
