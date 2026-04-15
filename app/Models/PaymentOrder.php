<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'subscription_id',
        'provider',
        'status',
        'amount_rub',
        'currency',
        'description',
        'tariff_plan',
        'tariff_period',
        'days',
        'devices',
        'quota_gb',
        'provider_link_id',
        'provider_transaction_id',
        'paid_at',
        'declined_at',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_rub' => 'integer',
            'days' => 'integer',
            'devices' => 'integer',
            'quota_gb' => 'integer',
            'paid_at' => 'datetime',
            'declined_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Subscription, self> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}

