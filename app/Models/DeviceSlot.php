<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Доп. устройство подписки: свой счётчик оплаченного времени (см. App\Services\Account\DeviceSlots).
 */
class DeviceSlot extends Model
{
    protected $fillable = [
        'subscription_id',
        'paid_ms_left',
        'anchor_ms',
        'payment_order_id',
    ];

    protected function casts(): array
    {
        return [
            'paid_ms_left' => 'integer',
            'anchor_ms' => 'integer',
        ];
    }

    /** @return BelongsTo<Subscription, self> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
