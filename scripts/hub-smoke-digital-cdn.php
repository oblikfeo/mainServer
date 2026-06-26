<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Subscription\SubscriptionExtraShareLines;

$lines = SubscriptionExtraShareLines::orderedWithBundle(['vless_entries' => []], false);
$last = end($lines);
echo 'count=' . count($lines) . PHP_EOL;
echo 'last_has_digital=' . (str_contains($last, 'nadezhda.digital') ? 'yes' : 'no') . PHP_EOL;
echo 'last_fragment=' . substr($last, strrpos($last, '#') + 1) . PHP_EOL;

$trial = SubscriptionExtraShareLines::linesForTestKey('vless://trial@1.2.3.4:443#trial');
$lastTrial = end($trial);
echo 'test_last_has_digital=' . (str_contains($lastTrial, 'nadezhda.digital') ? 'yes' : 'no') . PHP_EOL;
