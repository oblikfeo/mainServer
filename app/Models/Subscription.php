<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    private const BYTES_PER_GB = 1_073_741_824;

    protected $fillable = [
        'token',
        'fi_sub_id',
        'nl_sub_id',
        'quota_gb',
        'expiry_ms',
        'devices',
    ];

    protected function casts(): array
    {
        return [
            'quota_gb' => 'integer',
            'expiry_ms' => 'integer',
            'devices' => 'integer',
        ];
    }

    public static function bundleNodeCount(): int
    {
        $order = config('xui.bundle_order', ['fi', 'nl']);

        return max(1, count($order));
    }

    /** Лимит в байтах на один узел (как в 3x-ui totalGB). */
    public function perNodeTotalBytes(): int
    {
        $n = self::bundleNodeCount();

        return max(1, intdiv((int) $this->quota_gb * self::BYTES_PER_GB, $n));
    }

}
