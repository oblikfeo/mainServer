<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestKey;
use App\Models\User;
use App\Services\TestKeys\TestKeyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'hours' => ['nullable', 'integer', 'min:1', 'max:48'],
        ], [
            'email.required' => 'Укажите email пользователя.',
            'email.email' => 'Некорректный email.',
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return back()->withErrors([
                'email' => 'Пользователь с таким email не найден (должен быть зарегистрирован).',
            ]);
        }

        $hours = isset($validated['hours']) ? (int) $validated['hours'] : null;

        $key = $manager->issueForUser($user, $hours);

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

