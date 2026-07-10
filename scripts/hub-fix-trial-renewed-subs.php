<?php

declare(strict_types=1);

/**
 * Одноразовое исправление: триалы с оплаченным renew, но is_trial=true.
 *
 * Usage: php scripts/hub-fix-trial-renewed-subs.php
 */

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Services\Xui\XuiSubscriptionLimitIpSync;
use App\Services\Xui\XuiSubscriptionQuotaSync;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ids = PaymentOrder::query()
    ->where('purpose', 'renew')
    ->where('status', 'paid')
    ->whereNotNull('subscription_id')
    ->pluck('subscription_id');

$broken = Subscription::query()
    ->where('is_trial', true)
    ->whereIn('id', $ids)
    ->get();

if ($broken->isEmpty()) {
    echo "OK: nothing to fix\n";
    exit(0);
}

$quotaSync = app(XuiSubscriptionQuotaSync::class);
$limitSync = app(XuiSubscriptionLimitIpSync::class);
$fixed = 0;

foreach ($broken as $sub) {
    $order = PaymentOrder::query()
        ->where('subscription_id', $sub->id)
        ->where('purpose', 'renew')
        ->where('status', 'paid')
        ->orderByDesc('id')
        ->first();

    $plan = (string) ($order?->tariff_plan ?? '');
    $planDevices = 0;
    if ($plan !== '' && $plan !== 'bonus') {
        $products = config('payments.products', []);
        $planDevices = max(0, (int) (is_array($products[$plan] ?? null) ? ($products[$plan]['devices'] ?? 0) : 0));
    }

    $sub->is_trial = false;
    if ($planDevices > 0) {
        $sub->devices = max((int) $sub->devices, $planDevices);
    }
    $sub->save();

    $quotaSync->syncForSubscription($sub);
    $limitSync->syncForSubscription($sub);

    echo 'fixed subscription '.$sub->id.' user='.$sub->user_id.' plan='.$plan.' devices='.(int) $sub->devices.PHP_EOL;
    $fixed++;
}

echo "OK: fixed {$fixed} subscription(s)\n";
