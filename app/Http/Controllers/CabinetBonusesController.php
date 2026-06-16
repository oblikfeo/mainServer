<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\Payments\BonusExtraDevicePricing;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CabinetBonusesController extends Controller
{
    public function __invoke(BonusExtraDevicePricing $pricing): View
    {
        $user = Auth::user();

        $bonusItems = collect();
        if ($user !== null && $pricing->isConfigured()) {
            $bonusItems = $user->subscriptions()
                ->where('is_trial', false)
                ->orderByDesc('id')
                ->get()
                ->filter(fn (Subscription $sub) => ! $sub->isExpired())
                ->map(function (Subscription $sub) use ($pricing): array {
                    $remainingDays = $pricing->remainingActiveDays($sub);

                    return [
                        'subscription' => $sub,
                        'remaining_days' => $remainingDays,
                        'amount_rub' => $pricing->amountRubForSubscription($sub),
                        'tier_range' => $pricing->tierRangeLabel($remainingDays),
                    ];
                })
                ->filter(fn (array $row) => (int) $row['amount_rub'] > 0)
                ->values();
        }

        return view('cabinet.bonuses.index', [
            'bonusItems' => $bonusItems,
            'bonusAddDevices' => $pricing->addDevices(),
            'bonusConfigured' => $pricing->isConfigured(),
            'pricingTiers' => $pricing->displayTiers(),
            'stepRub' => $pricing->stepRub(),
            'dayBucket' => $pricing->dayBucket(),
        ]);
    }
}
