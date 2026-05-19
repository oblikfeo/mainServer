<?php

namespace App\Services\Subscription;

use App\Models\Subscription;

/**
 * subscription-userinfo для Happ: expire=0 на iOS часто ломает импорт ссылок.
 */
final class SubscriptionFeedUserinfo
{
    /** Безлимит по времени (expiry_ms=0) — далёкая дата, не 0. */
    private const UNLIMITED_EXPIRE_UNIX = 4102444800;

    public static function expireUnixForSubscription(Subscription $sub): int
    {
        $ms = (int) $sub->expiry_ms;
        if ($ms > 0) {
            return (int) floor($ms / 1000);
        }

        return self::UNLIMITED_EXPIRE_UNIX;
    }

    public static function format(int $upload, int $download, int $totalBytes, int $expireUnix): string
    {
        return "upload={$upload}; download={$download}; total={$totalBytes}; expire={$expireUnix}";
    }
}
