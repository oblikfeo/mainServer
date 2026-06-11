<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$title = (string) config('xui.sub_extra_bg31.vless_title', '');
$enabled = filter_var(config('xui.sub_extra_bg31.enabled', false), FILTER_VALIDATE_BOOL);
$lines = App\Services\Subscription\SubscriptionExtraShareLines::lines();

echo 'bg31_enabled='.($enabled ? 'yes' : 'no')."\n";
echo 'bg31_title='.$title."\n";
echo 'share_lines='.count($lines)."\n";

$found = false;
foreach ($lines as $i => $line) {
    if (str_contains((string) $line, '31.22.10.250')) {
        echo "bg31_line_ok idx={$i}\n";
        $found = true;
    }
}
if (! $found) {
    fwrite(STDERR, "bg31_line_missing\n");
    exit(1);
}

$bundles = collect(config('links.bundles', []))->pluck('id')->all();
echo 'admin_bundles='.implode(',', $bundles)."\n";
if (! in_array('bg31', $bundles, true)) {
    fwrite(STDERR, "admin_bg31_missing\n");
    exit(1);
}

echo "SMOKE_BG31_OK\n";
