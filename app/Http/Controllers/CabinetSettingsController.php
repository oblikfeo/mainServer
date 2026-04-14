<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CabinetSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = $request->user()
            ->subscriptions()
            ->orderByDesc('created_at')
            ->get();

        return view('cabinet.settings', [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function detachDevice(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int) $subscription->user_id === (int) $user->id, 403);

        $validated = $request->validate([
            'hash' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
        ], [
            'hash.regex' => 'Некорректный идентификатор устройства.',
        ]);

        $target = $validated['hash'];
        $hashes = $subscription->bound_hwid_hashes;
        if (! is_array($hashes)) {
            $hashes = [];
        }
        $hashes = array_values(array_filter(
            $hashes,
            static fn ($h) => is_string($h) && strlen($h) === 64 && $h !== $target
        ));

        $subscription->bound_hwid_hashes = $hashes === [] ? null : $hashes;
        $subscription->save();

        return back()->with('status', 'device-unbound');
    }

    public function clearAllDevices(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int) $subscription->user_id === (int) $user->id, 403);

        $subscription->bound_hwid_hashes = null;
        $subscription->save();

        return back()->with('status', 'devices-cleared');
    }
}
