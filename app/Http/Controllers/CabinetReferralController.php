<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CabinetReferralController extends Controller
{
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $referralCode = (string) ($user->referral_code ?? '');
        $referralLink = $referralCode !== ''
            ? url('/register?ref='.urlencode($referralCode))
            : url('/register');

        return view('cabinet.referral.index', [
            'referralLink' => $referralLink,
        ]);
    }

    /** Второй вариант вёрстки (шкала-путь), временно по адресу /dashboard/referral2 */
    public function showV2(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $referralCode = (string) ($user->referral_code ?? '');
        $referralLink = $referralCode !== ''
            ? url('/register?ref='.urlencode($referralCode))
            : url('/register');

        return view('cabinet.referral.v2', [
            'referralLink' => $referralLink,
            'journeyFillPercent' => 42,
        ]);
    }
}
