<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        if (config('admin.username') === '' || config('admin.password') === '') {
            abort(503, 'Admin credentials are not set in environment.');
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        if (config('admin.username') === '' || config('admin.password') === '') {
            abort(503, 'Admin credentials are not set in environment.');
        }

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $userOk = hash_equals(config('admin.username'), $credentials['username']);
        $passOk = hash_equals(config('admin.password'), $credentials['password']);

        if (! $userOk || ! $passOk) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => __('Неверный логин или пароль.')]);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_auth', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_auth');
        $request->session()->regenerate();

        return redirect()->route('admin.login');
    }
}
