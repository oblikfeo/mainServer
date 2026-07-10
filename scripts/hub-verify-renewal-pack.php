<?php

declare(strict_types=1);

/**
 * Smoke-проверка ApplySubscriptionRenewalPack на боевом (без XUI, в откатываемой транзакции).
 *
 * Usage: php scripts/hub-verify-renewal-pack.php
 */

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\ApplySubscriptionRenewalPack;
use App\Services\Subscription\SubscriptionCalendarExtension;
use App\Services\Xui\XuiSubscriptionLimitIpSync;
use App\Services\Xui\XuiSubscriptionQuotaSync;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$calendar = new class extends SubscriptionCalendarExtension
{
    public function __construct() {}

    public function addCalendarDays(Subscription $subscription, float|int $days): void
    {
        $addMs = (int) round(((float) $days) * 86_400_000);
        $nowMs = (int) (now()->getTimestamp() * 1000);
        $base = max((int) $subscription->expiry_ms, $nowMs);
        $subscription->expiry_ms = $base + $addMs;
        $subscription->save();
    }
};

$noopQuota = new class extends XuiSubscriptionQuotaSync
{
    public function __construct() {}

    public function syncForSubscription(Subscription $sub): void {}
};

$noopLimit = new class extends XuiSubscriptionLimitIpSync
{
    public function __construct() {}

    public function syncForSubscription(Subscription $sub): void {}
};

$pack = new ApplySubscriptionRenewalPack($calendar, $noopQuota, $noopLimit);
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
