<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['wifi', 'wifi2'] as $bundleKey) {
    $node = config('xui.nodes.'.$bundleKey, []);
    echo "=== {$bundleKey} ===\n";
    echo 'panel_base='.(string) ($node['panel_base'] ?? '')."\n";
    echo 'panel_username='.(string) ($node['panel_username'] ?? '')."\n";
    echo 'inbound_id='.(int) ($node['inbound_id'] ?? 0)."\n";
    try {
        $client = new \App\Services\Xui\XuiPanelClient((string) ($node['panel_base'] ?? ''));
        $client->login((string) ($node['panel_username'] ?? ''), (string) ($node['panel_password'] ?? ''));
        $list = $client->getInboundsList();
        $ids = [];
        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }
            $ids[] = (int) ($row['id'] ?? 0);
        }
        sort($ids);
        echo 'inbounds_list_ids='.implode(',', $ids)."\n";
    } catch (\Throwable $e) {
        echo 'ERR: '.$e->getMessage()."\n";
    }
    echo "\n";
}
