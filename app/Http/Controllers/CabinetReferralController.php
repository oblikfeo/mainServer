<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Referral\ReferralCabinetViewData;
use App\Services\Referral\ReferralLinkBuilder;
use App\Services\Referral\ReferralMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CabinetReferralController extends Controller
{
    public function show(Request $request, ReferralMetrics $referralMetrics, ReferralLinkBuilder $referralLinks): View
    {
        /** @var User $user */
        $user = $request->user();
        $referralLink = $referralLinks->forUser($user);

        $quests = ReferralCabinetViewData::build($referralMetrics, $user);
        $referralHistory = $referralMetrics->referralHistoryCards($user);

        return view('cabinet.referral.index', [
            'referralLink' => $referralLink,
            'quests' => $quests,
            'referralHistory' => $referralHistory,
        ]);
    }
}
