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
        'public_code',
        'token',
        'wifi_sub_id',
        'wifi2_sub_id',
        'fi_sub_id',
        'nl_sub_id',
        'quota_gb',
        'expiry_ms',
        'devices',
        'bound_hwid_hashes',
        'bound_hwid_meta',
    ];

    protected function casts(): array
    {
        return [
            'public_code' => 'integer',
            'quota_gb' => 'integer',
            'expiry_ms' => 'integer',
            'devices' => 'integer',
            'bound_hwid_hashes' => 'array',
            'bound_hwid_meta' => 'array',
        ];
    }

    public static function generateUniquePublicCode(): int
    {
        for ($i = 0; $i < 100; $i++) {
            $code = random_int(10000, 99999);
            if (! static::query()->where('public_code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Не удалось сгенерировать уникальный public_code');
    }

    protected static function booted(): void
    {
        static::creating(function (Subscription $model) {
            if ($model->public_code === null) {
                $model->public_code = static::generateUniquePublicCode();
            }
        });
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