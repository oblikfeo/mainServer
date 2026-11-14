<?php

namespace App\Http\Controllers;

use App\Services\Account\AccountOrderFactory;
use App\Services\Account\AccountOverview;
use App\Services\Account\AccountPricing;
use App\Services\Account\DeviceSlots;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Оплата»: шаг 1 — сколько устройств, шаг 2 — срок. Доп. устройства действуют до конца подписки.
 */
class CabinetPaymentController extends Controller
{
    public function __invoke(Request $request): View
    {
        $account = AccountOverview::forUser($request->user());
        $sub = $account['subscription'];
        $active = in_array($account['state'], [AccountOverview::STATE_ACTIVE, AccountOverview::STATE_TRIAL], true);
        $base = AccountPricing::effectiveBase($sub);
        $paidActive = AccountPricing::isPaidActive($sub);
        $have = $base > 0 ? AccountOrderFactory::paidSlots($sub) : 0;
        $promoAvailable = AccountPricing::promoAvailableFor($sub);

        return view('cabinet.payment', [
            'account' => $account,
            // Данные для расчёта на странице (только для показа — цену заказа считает сервер по тем же правилам).
            'payConfig' => [
                'state' => $account['state'],
                'active' => $active,
                'paidActive' => $paidActive,
                'noExpiry' => $sub !== null && DeviceSlots::subscriptionActive($sub) && (int) $sub->expiry_ms <= 0,
                'expiresAt' => $account['expires_at']?->format('d.m.Y'),
                'expiresAtIso' => $account['expires_at']?->toIso8601String(),
                'restDays' => AccountPricing::paidDaysLeft($sub),
                'baseDevices' => $base,
                'unlimitedDevices' => $base <= 0,
                // Сколько устройств сейчас (для пробной — её 1 устройство, для оплаченной — базовые + доп.).
                'currentDevices' => $sub !== null && $base > 0 ? (int) $sub->devices + $have : 0,
                'paidExtra' => $have,
                'overBase' => AccountPricing::overBaseDevices($sub),
                'maxDevices' => max($base, AccountPricing::maxDevices()),
                'deviceMonthRub' => AccountPricing::deviceMonthRub(),
                'deviceMonthDays' => AccountPricing::deviceMonthDays(),
                'terms' => array_values(AccountPricing::terms()),
                'promo' => $promoAvailable ? AccountPricing::promo() : null,
                // Подарок по промокоду к первой оплате подписки (дни) — сервер добавит сам, здесь только для показа.
                'bonusDays' => (int) $request->user()->promo_bonus_days,
            ],
        ]);
    }
}
