<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentDonePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * WATA success URL (/spasibo) — тот же экран, что /buy/done, без редиректа.
 */
final class PaymentSuccessController extends Controller
{
    public function __invoke(Request $request, PaymentDonePage $donePage): View|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $order = $donePage->resolveOrder($request, $user);
        if ($order === null) {
            return redirect()->route('dashboard');
        }

        return view('quick-buy.done', $donePage->viewData($order));
    }
}
