<?php

namespace App\Http\Controllers;

use App\Services\Referral\ReferralLinkBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PaymentSuccessController extends Controller
{
    public function __invoke(Request $request, ReferralLinkBuilder $referralLinks): View
    {
        $referralLink = null;
        $user = $request->user();
        if ($user !== null) {
            $referralLink = $referralLinks->forUser($user);
        }

        return view('spasibo', [
            'referralLink' => $referralLink,
        ]);
    }
}
