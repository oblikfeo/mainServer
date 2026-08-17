<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CabinetPromoWelcomeController extends Controller
{
    public function dismiss(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user !== null && $user->promo_welcome_pending) {
            $user->forceFill(['promo_welcome_pending' => false])->save();
        }

        if ($request->input('action') === 'claim') {
            return redirect()
                ->route('dashboard', ['tab' => 'trial'])
                ->withFragment('cabinet-trial');
        }

        return back();
    }
}
