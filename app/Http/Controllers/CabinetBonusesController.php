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
                    return [
                        'subscription' => $sub,
                        'amount_rub' => $pricing->amountRubForSubscription($sub),
                    ];
                })
                ->filter(fn (array $row) => (int) $row['amount_rub'] > 0)
                ->values();
        }

        return view('cabinet.bonuses.index', [
            'bonusItems' => $bonusItems,
            'bonusAddDevices' => $pricing->addDevices(),
            'bonusConfigured' => $pricing->isConfigured(),
        ]);
    }
}
