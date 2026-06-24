<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HappPathProbeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WhatWorksController extends Controller
{
    public function index(HappPathProbeService $probes): View
    {
        $results = $probes->forPage();

        return view('admin.what_works', [
            'results' => $results,
            'cacheTtl' => max(10, (int) config('path_probe.cache_ttl', 120)),
        ]);
    }

    public function run(HappPathProbeService $probes): RedirectResponse
    {
        $probes->refreshAndCache();

        return redirect()
            ->route('admin.what_works')
            ->with('status', 'Проверка завершена.');
    }
}
