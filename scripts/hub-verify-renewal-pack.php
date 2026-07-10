<?php

declare(strict_types=1);

/**
 * Smoke-проверка ApplySubscriptionRenewalPack на боевом (в откатываемой транзакции).
 * XUI-синк может писать warning в лог — на результат проверки не влияет.
 *
 * Usage: php scripts/hub-verify-renewal-pack.php
 */

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\ApplySubscriptionRenewalPack;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** @var ApplySubscriptionRenewalPack $pack */
$pack = app(ApplySubscriptionRenewalPack::class);
$failures = [];

DB::beginTransaction();

try {
    $user = User::factory()->create(['email' => 'renewal-verify-'.bin2hex(random_bytes(4)).'@verify.local']);

    $trial = Subscription::query()->create([
        'user_id' => $user->id,
        'token' => 'verify-trial-'.bin2hex(random_bytes(6)),
        'fi_sub_id' => bin2hex(random_bytes(8)),
        'nl_sub_id' => bin2hex(random_bytes(8)),
        'quota_gb' => 5,
        'expiry_ms' => (int) ((time() + 3600) * 1000),
        'devices' => 1,
        'is_trial' => true,
    ]);

    $converted = $pack->apply($trial->id, 30, 100, 0, 'solo');
    if ($converted->is_trial) {
        $failures[] = 'trial renewal: is_trial still true';
    }
    if ((int) $converted->devices !== 2) {
        $failures[] = 'trial renewal: expected 2 devices, got '.(int) $converted->devices;
    }
    if ((int) $converted->quota_gb !== 105) {
        $failures[] = 'trial renewal: expected quota 105, got '.(int) $converted->quota_gb;
    }

    $paid = Subscription::query()->create([
        'user_id' => $user->id,
        'token' => 'verify-paid-'.bin2hex(random_bytes(6)),
        'fi_sub_id' => bin2hex(random_bytes(8)),
        'nl_sub_id' => bin2hex(random_bytes(8)),
        'quota_gb' => 100,
        'expiry_ms' => (int) ((time() + 86400) * 1000),
        'devices' => 2,
        'is_trial' => false,
    ]);
    $beforeExpiry = (int) $paid->expiry_ms;

    $renewed = $pack->apply($paid->id, 30, 50, 0, 'solo');
    if ($renewed->is_trial) {
        $failures[] = 'paid renewal: is_trial became true';
    }
    if ((int) $renewed->devices !== 2) {
        $failures[] = 'paid renewal: devices changed unexpectedly';
    }
    if ((int) $renewed->quota_gb !== 150) {
        $failures[] = 'paid renewal: expected quota 150, got '.(int) $renewed->quota_gb;
    }
    if ((int) $renewed->expiry_ms <= $beforeExpiry) {
        $failures[] = 'paid renewal: expiry did not increase';
    }

    DB::rollBack();

    if ($failures !== []) {
        fwrite(STDERR, "FAIL:\n- ".implode("\n- ", $failures)."\n");
        exit(1);
    }

    echo "OK: trial->paid conversion and paid renewal checks passed\n";
    exit(0);
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n");
    exit(1);
}
