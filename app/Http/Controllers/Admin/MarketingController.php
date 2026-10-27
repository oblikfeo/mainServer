<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingStats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Раздел «Маркетинг». Доступен основному админу и отдельному маркетинговому входу,
 * который видит только эту страницу.
 */
final class MarketingController extends Controller
{
    private const PRESETS = [7, 30, 90];

    public function index(Request $request, MarketingStats $stats): View
    {
        $tz = (string) config('app.timezone');
        $today = now($tz);

        $preset = (int) $request->query('days', 30);
        $from = $this->date($request->query('from'), $tz);
        $to = $this->date($request->query('to'), $tz);

        if ($from !== null && $to !== null && $from->lte($to)) {
            $preset = 0;
            $to = $to->min($today);
            $from = $from->max($to->copy()->subDays(365));
        } else {
            $preset = in_array($preset, self::PRESETS, true) ? $preset : 30;
            $to = $today->copy();
            $from = $today->copy()->subDays($preset - 1);
        }

        return view('admin.marketing', [
            'data' => $stats->build($from, $to),
            'preset' => $preset,
            'presets' => self::PRESETS,
            'marketingOnly' => $request->session()->get('admin_auth') !== true,
        ]);
    }

    private function date(mixed $value, string $tz): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value, $tz)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
