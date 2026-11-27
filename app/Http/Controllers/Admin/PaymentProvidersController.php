<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Админка: какая платёжка на сайте и в боте (wata | platega). */
class PaymentProvidersController extends Controller
{
    public function edit(): View
    {
        return view('admin.payment-providers', [
            'providers' => PaymentProviders::PROVIDERS,
            'site' => PaymentProviders::site(),
            'bot' => PaymentProviders::bot(),
            'configured' => array_map(fn ($p) => PaymentProviders::isConfigured($p), array_combine(array_keys(PaymentProviders::PROVIDERS), array_keys(PaymentProviders::PROVIDERS))),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $allowed = Rule::in(array_keys(PaymentProviders::PROVIDERS));
        $data = $request->validate([
            'site' => ['required', 'string', $allowed],
            'bot' => ['required', 'string', $allowed],
        ]);

        PaymentProviders::set(PaymentProviders::SITE_KEY, $data['site']);
        PaymentProviders::set(PaymentProviders::BOT_KEY, $data['bot']);

        return redirect()
            ->route('admin.payment_providers')
            ->with('status', 'Сохранено: сайт — '.PaymentProviders::PROVIDERS[$data['site']].', бот — '.PaymentProviders::PROVIDERS[$data['bot']].'. Действует сразу для новых оплат.');
    }
}
