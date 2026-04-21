<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Верстка раздела «Реферальная система». Данные для макета — заглушки во view.
 */
final class CabinetReferralController extends Controller
{
    public function show(): View
    {
        return view('cabinet.referral.index');
    }
}
