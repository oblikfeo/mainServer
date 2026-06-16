<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CabinetBonusesController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $bonusCfg = config('payments.bonus_extra_device', []);
        $amountRub = (int) ($bonusCfg['amount_rub'] ?? 0);
        $addDevices = (int) ($bonusCfg['add_devices'] ?? 0);

        $activeSubscriptions = collect();
        if ($user !== null) {
            $activeSubscriptions = $user->subscriptions()
                ->where('is_trial', false)
                ->orderByDesc('id')
                ->get()
                ->filter(fn (Subscription $sub) => ! $sub->isExpired())
                ->values();
        }

        return view('cabinet.bonuses.index', [
            'activeSubscriptions' => $activeSubscriptions,
            'bonusAmountRub' => $amountRub,
            'bonusAddDevices' => $addDevices,
            'bonusConfigured' => $amountRub > 0 && $addDevices > 0,
        ]);
    }
}
