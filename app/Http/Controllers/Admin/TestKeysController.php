<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\DestroySubscription;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\TestKeys\TestKeyManager;
use App\Services\Xui\XuiPanelException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TestKeysController extends Controller
{
    public function index(Request $request): View
    {
        $q = Subscription::query()->with('user')->where('is_trial', true)->orderByDesc('id');

        if ($request->get('state') === 'active') {
            $nowMs = (int) (now()->getTimestamp() * 1000);
            $q->where('expiry_ms', '>', $nowMs);
        } elseif ($request->get('state') === 'expired') {
            $nowMs = (int) (now()->getTimestamp() * 1000);
            $q->where('expiry_ms', '>', 0)->where('expiry_ms', '<=', $nowMs);
        }

        if (filled($request->get('email'))) {
            $email = trim((string) $request->get('email'));
            $q->whereHas('user', fn ($u) => $u->where('email', $email));
        }

        return view('admin.test_keys.index', [
            'items' => $q->paginate(50)->withQueryString(),
        ]);
    }

    public function store(Request $request, TrialSubscriptionIssuer $issuer): RedirectResponse
    {
        $adminCap = max(48, (int) config('trial_subscription.admin_hours_max', 8760));

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'hours' => ['nullable', 'integer', 'min:1', 'max:'.$adminCap],
            'create_user' => ['sometimes', 'boolean'],
        ], [
            'email.required' => 'Укажите email пользователя.',
            'email.email' => 'Некорректный email.',
            'hours.max' => 'Слишком большой срок для админской выдачи (максимум '.$adminCap.' ч).',
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            if (! $request->boolean('create_user')) {
                return back()->withErrors([
                    'email' => 'Пользователь не найден. Включите «Нет аккаунта — создать» или попросите зарегистрироваться.',
                ]);
            }

            $user = User::query()->create([
                'name' => strtok($email, '@') ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::password(24)),
                'email_verified_at' => now(),
            ]);
        }

        $hours = isset($validated['hours']) ? (int) $validated['hours'] : null;

        try {
            $result = $issuer->issueFromAdmin($user, $hours);
        } catch (XuiPanelException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Не удалось выдать тест: '.$e->getMessage(),
                ]);
        } catch (\Throwable) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Не удалось выдать тестовую подписку. Проверьте узлы XUI и конфигурацию.',
                ]);
        }

        return redirect()
            ->route('admin.test_keys')
            ->with('status', 'issued:'.$result->subscription->id);
    }

    public function revoke(Subscription $subscription, DestroySubscription $destroyer): RedirectResponse
    {
        if (! $subscription->is_trial) {
            abort(404);
        }

        $id = $subscription->id;

        try {
            $destroyer->destroy($subscription);
        } catch (XuiPanelException $e) {
            return back()->withErrors([
                'email' => 'Не удалось снять подписку: '.$e->getMessage(),
            ]);
        }

        return back()->with('status', 'revoked:'.$id);
    }

    /**
     * Удаляет истёкшие пробные подписки с панелей и из БД; плюс legacy test_keys через старую панель.
     */
    public function cleanup(DestroySubscription $destroyer, TestKeyManager $legacyKeys): RedirectResponse
    {
        $n = 0;
        $nowMs = (int) (now()->getTimestamp() * 1000);

        $expiredTrials = Subscription::query()
            ->where('is_trial', true)
            ->where('expiry_ms', '>', 0)
            ->where('expiry_ms', '<=', $nowMs)
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($expiredTrials as $sub) {
            try {
                $destroyer->destroy($sub);
                $n++;
            } catch (\Throwable) {
                // продолжаем остальные
            }
        }

        $n += $legacyKeys->cleanupExpired();

        return back()->with('status', 'cleanup:'.$n);
    }
}
