<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestKey;
use App\Models\User;
use App\Services\TestKeys\TestKeyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TestKeysController extends Controller
{
    public function index(Request $request): View
    {
        $q = TestKey::query()->with('user')->orderByDesc('id');

        if ($request->get('state') === 'active') {
            $q->whereNull('revoked_at')->where('expires_at', '>', now());
        } elseif ($request->get('state') === 'expired') {
            $q->whereNull('revoked_at')->where('expires_at', '<=', now());
        } elseif ($request->get('state') === 'revoked') {
            $q->whereNotNull('revoked_at');
        }

        if (filled($request->get('email'))) {
            $email = trim((string) $request->get('email'));
            $q->whereHas('user', fn ($u) => $u->where('email', $email));
        }

        return view('admin.test_keys.index', [
            'items' => $q->paginate(50)->withQueryString(),
        ]);
    }

    public function store(Request $request, TestKeyManager $manager): RedirectResponse
    {
        $adminCap = max(48, (int) config('test_keys.admin_issue_max_hours', 8760));

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
            $key = $manager->issueForUser(
                $user,
                $hours,
                applyReferralTestCreditHours: false,
                durationHoursCap: $adminCap,
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Не удалось выдать ключ: проверьте настройку тестовой связки или панель 3x-ui.',
                ]);
        }

        return redirect()
            ->route('admin.test_keys')
            ->with('status', 'issued:'.$key->id);
    }

    public function revoke(TestKey $testKey, TestKeyManager $manager): RedirectResponse
    {
        $manager->revoke($testKey, 'manual');

        return back()->with('status', 'revoked:'.$testKey->id);
    }

    public function cleanup(TestKeyManager $manager): RedirectResponse
    {
        $n = $manager->cleanupExpired();

        return back()->with('status', 'cleanup:'.$n);
    }
}

