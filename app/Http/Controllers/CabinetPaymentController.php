<?php

namespace App\Http\Controllers;

use App\Services\Account\AccountOverview;
use App\Services\Account\AccountPricing;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Оплата»: покупка, продление и доп. устройства на одной странице.
 */
class CabinetPaymentController extends Controller
{
    public function __invoke(Request $request): View
    {
        $account = AccountOverview::forUser($request->user());
        $active = in_array($account['state'], [AccountOverview::STATE_ACTIVE, AccountOverview::STATE_TRIAL], true);
        $base = $account['unlimited_devices'] ? 0 : max(1, (int) $account['base_devices']);

        return view('cabinet.payment', [
            'account' => $account,
            // Данные для конструктора на странице (расчёт на клиенте только для показа — цену считает сервер).
            'payConfig' => [
                'state' => $account['state'],
                'active' => $active,
                'expiresAt' => $account['expires_at']?->format('d.m.Y'),
                'expiresAtIso' => $account['expires_at']?->toIso8601String(),
                'daysLeft' => $account['days_left'],
                'baseDevices' => $base,
                'unlimitedDevices' => (bool) $account['unlimited_devices'],
                'paidExtra' => (int) $account['extra_total'],
                // Базовые устройства сверх стандартных оплачиваются при продлении как доп. устройства.
                'overBase' => AccountPricing::overBaseDevices($account['subscription']),
                'maxDevices' => $base + (int) $account['max_extra'],
                'deviceMonthRub' => AccountPricing::deviceMonthRub(),
                'deviceMaxMonths' => AccountPricing::deviceMaxMonths(),
                'terms' => array_values(AccountPricing::terms()),
            ],
        ]);
    }
}
