<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CabinetPaymentController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $renewalSubscriptions = $user !== null
            ? $user->subscriptions()->orderByDesc('id')->get()
            : collect();

        $soloDeviceCap = (int) config('payments.products.solo.devices', 2);

        return view('cabinet.payment', [
            'renewalSubscriptions' => $renewalSubscriptions,
            'soloDeviceCap' => $soloDeviceCap,
        ]);
    }
}
