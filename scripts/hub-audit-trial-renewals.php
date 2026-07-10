<?php

declare(strict_types=1);

use App\Models\PaymentOrder;
use App\Models\Subscription;

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
    ->get(['id', 'user_id', 'devices', 'quota_gb', 'expiry_ms']);

echo 'broken_trial_after_paid_renew='.$broken->count().PHP_EOL;
foreach ($broken as $s) {
    echo $s->id.' user='.$s->user_id.' dev='.$s->devices.' quota='.$s->quota_gb.PHP_EOL;
}
