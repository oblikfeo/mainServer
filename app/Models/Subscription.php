<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    private const BYTES_PER_GB = 1_073_741_824;

    protected $fillable = [
        'user_id',
        'token',
        'fi_sub_id',
        'nl_sub_id',
        'quota_gb',
        'expiry_ms',
        'devices',
        'bound_hwid_hashes',
    ];

    protected function casts(): array
    {
        return [
            'quota_gb' => 'integer',
            'expiry_ms' => 'integer',
            'devices' => 'integer',
            'bound_hwid_hashes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function expiresAt(): ?Carbon
    {
        $ms = (int) $this->expiry_ms;
        if ($ms <= 0) {
            return null;
        }

        return Carbon::createFromTimestampMs($ms);
    }

    public function isExpired(): bool
    {
        $at = $this->expiresAt();

        return $at === null ? false : $at->isPast();
    }

}