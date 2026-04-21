<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Subscription\DestroySubscription;
use App\Services\Xui\XuiPanelException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function create(): View
    {
        return view('admin.subscription.create');
    }

    public function store(Request $request, CreateDualBundleSubscription $service): RedirectResponse
    {
        $data = $request->validate([
            'devices' => ['required', 'integer', 'min:1', 'max:5'],
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'gb' => ['required', 'integer', 'min:1', 'max:50000'],
        ]);

        $lockKey = 'admin:subscription:create:'.$request->session()->getId();
        if (! Cache::add($lockKey, true, now()->addSeconds(20))) {
            return back()
                ->withInput()
                ->withErrors(['xui' => 'Создание уже выполняется. Дождитесь завершения и обновите страницу.']);
        }

        try {
            $result = $service->create(
                (int) $data['devices'],
                (int) $data['days'],
                (int) $data['gb'],
            );
        } catch (XuiPanelException $e) {
            return back()
                ->withInput()
                ->withErrors(['xui' => $e->getMessage()]);
        } finally {
            Cache::forget($lockKey);
        }

        return redirect()
            ->route('admin.subscription.show', $result->subscription)
            ->with('subscription_result', [
                'subscription_url' => $result->subscriptionUrl,
                'wifi_vless' => $result->wifiVlessLine,
                'fi_vless' => $result->fiVlessLine,
                'nl_vless' => $result->nlVlessLine,
                'decode_warning' => $result->decodeWarning,
            ]);
    }

    public function show(Subscription $subscription, CreateDualBundleSubscription $service): View
    {
        $subscription->loadMissing('user');

        $payload = session('subscription_result');

        if (is_array($payload)) {
            $subscriptionUrl = $payload['subscription_url'] ?? url('/sub/'.$subscription->token);
            $wifiVless = $payload['wifi_vless'] ?? '';
            $fiVless = $payload['fi_vless'] ?? '';
            $nlVless = $payload['nl_vless'] ?? '';
            $decodeWarning = $payload['decode_warning'] ?? null;
        } else {
            $subscriptionUrl = url('/sub/'.$subscription->token);
            $decoded = $service->decodeLinesForSubscription($subscription);
            $wifiVless = $decoded['wifi'];
            $fiVless = $decoded['fi'];
            $nlVless = $decoded['nl'];
            $decodeWarning = $decoded['warning'];
        }

        return view('admin.subscription.show', [
            'subscription' => $subscription,
            'subscriptionUrl' => $subscriptionUrl,
            'wifiVless' => $wifiVless,
            'fiVless' => $fiVless,
            'nlVless' => $nlVless,
            'decodeWarning' => $decodeWarning,
        ]);
    }

    public function attachOwner(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'owner_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'exists:users,email'],
        ]);

        $user = User::query()->where('email', $data['owner_email'])->firstOrFail();
        $subscription->user_id = $user->id;
        $subscription->save();

        return back()->with('status', 'Подписка #'.$subscription->id.' привязана к '.$user->email.'.');
    }

    public function destroy(Subscription $subscription, DestroySubscription $destroyer): RedirectResponse
    {
        try {
            $destroyer->destroy($subscription);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.report')
                ->withErrors(['xui' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.report')
            ->with('status', 'Подписка удалена (панели FI/NL и запись в БД).');
    }
}
