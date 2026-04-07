<?php

declare(strict_types=1);

$root = $argv[1] ?? dirname(__DIR__);
chdir($root);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$feedHwid = config('xui.feed_require_hwid');
$payload = app(App\Services\Xui\XuiSubscriptionConnectionInspector::class)->inspectAllActive();

$rows = \App\Models\Subscription::query()
    ->whereNotNull('fi_sub_id')
    ->whereNotNull('nl_sub_id')
    ->get(['id', 'devices', 'bound_hwid_hashes', 'fi_sub_id', 'nl_sub_id']);

$locks = [];
foreach ($rows as $s) {
    $bh = $s->bound_hwid_hashes;
    $locks[$s->id] = [
        'devices' => $s->devices,
        'bound_hwid_count' => is_array($bh) ? count($bh) : 0,
    ];
}

echo json_encode([
    'config' => [
        'feed_require_hwid' => $feedHwid,
    ],
    'inspector_errors' => $payload['errors'],
    'by_subscription' => $payload['by_subscription_id'],
    'subscriptions' => $locks,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
