<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Referral\ReferralCodeGenerator;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->referral_code === null || $user->referral_code === '') {
                $user->referral_code = ReferralCodeGenerator::unique();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'referral_pending_unlimited_traffic' => 'boolean',
            'referral_subscription_credit_days' => 'decimal:2',
            'telegram_linked_at' => 'datetime',
            'telegram_bot_blocked_at' => 'datetime',
            'telegram_id' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<Purchase, self> */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /** @return HasMany<TestKey, self> */
    public function testKeys(): HasMany
    {
        return $this->hasMany(TestKey::class);
    }

    /** Пользователи, зарегистрировавшиеся по реферальной ссылке этого аккаунта. */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    /** Пригласивший пользователь (если есть). */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    /**
     * Скрывать блок «Тестовая подписка» для самостоятельной выдачи (кнопка в ЛК), если уже есть активная платная подписка.
     * Пробные подписки (is_trial) не считаются платными.
     */
    public function shouldHideTestSubscriptionOffer(): bool
    {
        return $this->hasActiveNonTrialSubscription();
    }

    public function hasActiveNonTrialSubscription(): bool
    {
        $nowMs = (int) (now()->getTimestamp() * 1000);

        return $this->subscriptions()
            ->where('is_trial', false)
            ->where(function ($q) use ($nowMs) {
                $q->where('expiry_ms', '<=', 0)
                    ->orWhere('expiry_ms', '>', $nowMs);
            })
            ->exists();
    }

    /** Активная пробная подписка (по сроку expiry_ms). */
    public function activeTrialSubscription(): ?Subscription
    {
        $nowMs = (int) (now()->getTimestamp() * 1000);

        return $this->subscriptions()
            ->where('is_trial', true)
            ->where('expiry_ms', '>', $nowMs)
            ->orderByDesc('id')
            ->first();
    }

    /** Была ли когда-либо самовыдача пробного доступа из ЛК (включая legacy test_keys). */
    public function hasEverUsedCabinetTrial(): bool
    {
        if ($this->subscriptions()->where('is_trial', true)->exists()) {
            return true;
        }

        return $this->testKeys()->exists();
    }

    /**
     * Можно ли запросить новый пробный доступ из ЛК: один базовый тест на аккаунт,
     * плюс отдельные слоты referral_invitee_test_issues_remaining для приглашённых.
     */
    public function canSelfIssueCabinetTrial(): bool
    {
        if ($this->activeTrialSubscription() !== null) {
            return false;
        }

        if ($this->testKeys()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists()) {
            return false;
        }

        if ((int) $this->referral_invitee_test_issues_remaining > 0) {
            return true;
        }

        return ! $this->hasEverUsedCabinetTrial();
    }
}