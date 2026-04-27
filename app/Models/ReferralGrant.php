<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralGrant extends Model
{
    public const KIND_FIRST_REG_REFERRER_DAYS = 'first_reg_referrer_days';

    public const KIND_FIRST_REG_REFEREE_TEST_CREDIT = 'first_reg_referee_test_credit';

    public const KIND_FIRST_PAYMENT_PAIR = 'first_payment_pair';

    public const KIND_MILESTONE_EXTRA_DEVICE = 'milestone_extra_device';

    public const KIND_MILESTONE_UNLIMITED_TRAFFIC = 'milestone_unlimited_traffic';

    protected $fillable = [
        'grant_key',
        'kind',
        'beneficiary_user_id',
        'referee_user_id',
        'purchase_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
