<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$enabled = App\Services\Subscription\SubscriptionExtraShareLines::nlSharedConfigured();
$order = App\Services\Subscription\SubscriptionExtraShareLines::panelBundleOrder();
$lines = App\Services\Subscription\SubscriptionExtraShareLines::lines();

echo 'nl_shared='.($enabled ? '1' : '0')."\n";
echo 'panel_order='.implode(',', $order)."\n";
echo 'leading_shared_count='.count($lines)."\n";

$token = $argv[1] ?? '';
if ($token === '') {
    $row = App\Models\Subscription::query()->whereNull('revoked_at')->orderByDesc('id')->first();
    $token = $row?->token ?? '';
}
if ($token === '') {
    echo "no_token\n";
    exit(0);
}

$sub = App\Models\Subscription::query()->where('token', $token)->first();
if ($sub === null) {
    echo "subscription_not_found\n";
    exit(1);
}

$renderer = app(App\Services\Subscription\MergedSubscriptionFeedRenderer::class);
$resp = $renderer->render($sub);
$body = (string) $resp->getContent();
$vless = array_values(array_filter(explode("\n", $body), static fn (string $l): bool => str_starts_with(trim($l), 'vless://')));

echo 'token_tail='.substr($token, -6)."\n";
echo 'vless_count='.count($vless)."\n";
foreach ($vless as $i => $line) {
    $host = '';
    if (preg_match('/@([^:?]+)/', $line, $m)) {
        $host = $m[1];
    }
    echo 'line_'.$i.'_host='.$host."\n";
    if (str_contains($line, '158.160.136.187')) {
        echo "nl_shared_present=1\n";
    }
    if (str_contains($line, '158.160.208.31')) {
        echo "old_nl_present=1\n";
    }
}
