<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionSettingsController extends Controller
{
    public function edit(): View
    {
        $stored = AppSetting::getValue('happ_profile_title');
        $profileTitle = ($stored !== null && $stored !== '')
            ? $stored
            : (string) config('xui.sub_profile_title', 'nadezhda VPN');

        return view('admin.subscription.settings', [
            'profileTitle' => $profileTitle,
            'fromEnvDefault' => (string) config('xui.sub_profile_title', 'nadezhda VPN'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'profile_title' => ['nullable', 'string', 'max:25'],
        ]);

        $v = trim((string) ($data['profile_title'] ?? ''));
        if ($v === '') {
            AppSetting::forgetKey('happ_profile_title');
        } else {
            AppSetting::setValue('happ_profile_title', $v);
        }

        return redirect()
            ->route('admin.subscription.settings')
            ->with('status', 'Сохранено. В Happ обновите подписку.');
    }
}
