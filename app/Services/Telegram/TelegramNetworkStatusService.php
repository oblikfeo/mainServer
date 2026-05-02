<?php

namespace App\Services\Telegram;

use App\Services\BundleHealthChecker;
use Illuminate\Support\Facades\Cache;

/**
 * Агрегированный статус узлов для кнопки «Статус сети» в Telegram (ТЗ п.4).
 */
final class TelegramNetworkStatusService
{
    public function __construct(
        private BundleHealthChecker $bundleHealth
    ) {}

    /**
     * «Зелёный» — если все связки из config('links.bundles') считаются online; иначе «красный».
     */
    public function allOperational(): bool
    {
        $healthTtl = max(10, (int) config('links.health.cache_ttl', 30));

        $bundles = config('links.bundles', []);
        if ($bundles === []) {
            return true;
        }

        foreach ($bundles as $bundle) {
            if (! is_array($bundle)) {
                continue;
            }
            $id = (string) ($bundle['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $online = Cache::remember(
                'tg_bundle_health_v1_'.$id,
                $healthTtl,
                function () use ($bundle): bool {
                    $ev = $this->bundleHealth->evaluateBundle($bundle);

                    return (bool) ($ev['online'] ?? false);
                }
            );

            if (! $online) {
                return false;
            }
        }

        return true;
    }
}
