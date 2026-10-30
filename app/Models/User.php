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
use Illuminate\Support\Carbon;

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
            'trial_followup_email_sent_at' => 'datetime',
            'password' => 'hashed',
            'referral_pending_unlimited_traffic' => 'boolean',
            'referral_subscription_credit_days' => 'decimal:2',
            'telegram_linked_at' => 'datetime',
            'telegram_bot_blocked_at' => 'datetime',
            'telegram_id' => 'integer',
            'promo_welcome_pending' => 'boolean',
            'promo_bonus_days' => 'integer',
            'utm_campaign_at' => 'datetime',
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

    /** @return HasMany<PaymentOrder, self> */
    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
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

    /**
     * Пробные подписки аккаунта за всё время, включая ушедшие в архив при переходе
     * на «аккаунт = подписка» (у них user_id пуст, владелец записан в archived_from_user_id).
     */
    public function everTrialSubscriptions(): \Illuminate\Database\Eloquent\Builder
    {
        $id = $this->id;

        return Subscription::query()
            ->where('is_trial', true)
            ->where(static function ($q) use ($id): void {
                $q->where('user_id', $id)->orWhere('archived_from_user_id', $id);
            });
    }

    /** Была ли когда-либо самовыдача пробного доступа из ЛК (включая legacy test_keys). Промо-триал не считается. */
    public function hasEverUsedCabinetTrial(): bool
    {
        if ($this->everTrialSubscriptions()->whereNull('promo_code')->exists()) {
            return true;
        }

        return $this->testKeys()->exists();
    }

    /**
     * Можно ли запросить новый пробный доступ самостоятельно (ЛК или бот):
     * строго один тест на аккаунт, реферальные слоты повторную выдачу не дают.
     * Повторная выдача — только вручную из админки (issueFromAdmin, без этой проверки).
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

        // У аккаунта одна подписка: пробный период после оплаты уже не выдаётся.
        if ($this->hasEverPaid()) {
            return false;
        }

        return ! $this->hasEverUsedCabinetTrial();
    }

    /** Есть ли сейчас активный триал (пробная подписка или legacy test_key). */
    public function hasActiveTrialAccess(): bool
    {
        if ($this->activeTrialSubscription() !== null) {
            return true;
        }

        return $this->testKeys()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /** Был ли когда-либо оплаченный заказ или платная подписка. */
    public function hasEverPaid(): bool
    {
        if ($this->paymentOrders()->where('status', 'paid')->exists()) {
            return true;
        }

        return $this->subscriptions()->where('is_trial', false)->exists();
    }

    /**
     * Пустой аккаунт после неудачной оплаты в 3 клика (или незавершённой регистрации без ключа).
     * Такую почту можно снова использовать в /buy.
     */
    public function isReleasableQuickBuyPlaceholder(): bool
    {
        if ($this->email_verified_at !== null || $this->telegram_id !== null) {
            return false;
        }

        if ($this->subscriptions()->exists() || $this->testKeys()->exists()) {
            return false;
        }

        return ! $this->hasEverPaid();
    }

    /** Момент окончания последнего триала (пробная подписка или test_key), если был. */
    public function latestTrialEndedAt(): ?Carbon
    {
        $latest = null;

        foreach ($this->everTrialSubscriptions()->get(['expiry_ms']) as $trialSub) {
            $at = $trialSub->expiresAt();
            if ($at !== null && ($latest === null || $at->gt($latest))) {
                $latest = $at;
            }
        }

        foreach ($this->testKeys()->get(['expires_at']) as $testKey) {
            $at = $testKey->expires_at instanceof Carbon
                ? $testKey->expires_at
                : ($testKey->expires_at !== null ? Carbon::parse($testKey->expires_at) : null);
            if ($at !== null && ($latest === null || $at->gt($latest))) {
                $latest = $at;
            }
        }

        return $latest;
    }

    /** Окончание последней пробной подписки (subscriptions.is_trial), без legacy test_keys. */
    public function latestSubscriptionTrialEndedAt(): ?Carbon
    {
        $latest = null;

        foreach ($this->everTrialSubscriptions()->get(['expiry_ms']) as $trialSub) {
            $at = $trialSub->expiresAt();
            if ($at !== null && ($latest === null || $at->gt($latest))) {
                $latest = $at;
            }
        }

        return $latest;
    }

    public function hadAnyTrial(): bool
    {
        if ($this->everTrialSubscriptions()->exists()) {
            return true;
        }

        return $this->testKeys()->exists();
    }

    /** Был ли триал через пробную подписку (не legacy test_key). */
    public function hadSubscriptionTrial(): bool
    {
        return $this->everTrialSubscriptions()->exists();
    }

    /** Подтверждённая почта или привязанный Telegram — достаточно для пробного доступа. */
    public function hasVerifiedIdentity(): bool
    {
        return $this->hasVerifiedEmail() || $this->telegram_id !== null;
    }

    /** Почта для формы профиля (скрывает служебный адрес TG-only аккаунтов). */
    public function profileEmailValue(): string
    {
        if (\App\Services\Telegram\TelegramBotRegistrationService::isPlaceholderTelegramEmail($this->email)) {
            return '';
        }

        return $this->email;
    }
}