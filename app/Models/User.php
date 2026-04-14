<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<Purchase, self> */
    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions
            ->contains(fn (Subscription $subscription) => ! $subscription->isExpired());
    }

    /**
     * Есть запись о покупке и сейчас активна хотя бы одна подписка — тестовый ключ не показываем.
     */
    public function shouldHideTestSubscriptionOffer(): bool
    {
        return $this->purchases()->exists() && $this->hasActiveSubscription();
    }
}