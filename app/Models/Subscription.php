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
        'fi_sub_id',
        'nl_sub_id',
        'hy2_username',
        'hy2_password',
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

    /** Лимит в байтах для каждого узла (как в 3x-ui totalGB). */
    public function perNodeTotalBytes(): int
    {
        $quotaGb = (int) $this->quota_gb;
        if ($quotaGb <= 0) {
            return 0;
        }

        return $quotaGb * self::BYTES_PER_GB;
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