<?php

namespace App\Models;

use App\Services\Account\DeviceSlots;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'quota_gb',
        'expiry_ms',
        'devices',
        'is_trial',
        'promo_code',
        'bound_hwid_hashes',
        'bound_hwid_meta',
        'archived_from_user_id',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'public_code' => 'integer',
            'quota_gb' => 'integer',
            'expiry_ms' => 'integer',
            'devices' => 'integer',
            'is_trial' => 'boolean',
            'bound_hwid_hashes' => 'array',
            'bound_hwid_meta' => 'array',
            'archived_at' => 'datetime',
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

    /**
     * Подписка по токену из ссылки. Старая ссылка аккаунта (подписка ушла в архив при переходе
     * на «аккаунт = подписка») ведёт на его нынешнюю подписку — менять ссылку в приложении не нужно.
     */
    public static function resolveByToken(string $token): ?self
    {
        $sub = self::query()->where('token', $token)->first();
        if ($sub === null || $sub->archived_from_user_id === null) {
            return $sub;
        }

        return self::query()->where('user_id', $sub->archived_from_user_id)->first() ?? $sub;
    }

    public function isExpired(): bool
    {
        $at = $this->expiresAt();

        return $at === null ? false : $at->isPast();
    }

    public function shareableSubUrl(): string
    {
        return url('/sub/'.$this->token);
    }

    /** @return HasMany<DeviceSlot, self> */
    public function deviceSlots(): HasMany
    {
        return $this->hasMany(DeviceSlot::class);
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Сколько устройств может быть привязано сейчас: базовые (devices) + оплаченные доп. слоты.
     * 0 — без ограничения (особые подписки с devices = 0).
     */
    public function deviceLimit(?int $nowMs = null): int
    {
        $base = (int) $this->devices;
        if ($base <= 0) {
            return 0;
        }

        return $base + DeviceSlots::activeCount($this, $nowMs);
    }

}