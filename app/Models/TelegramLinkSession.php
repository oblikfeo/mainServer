<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLinkSession extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'otp_code_hash',
        'telegram_user_id',
        'telegram_chat_id',
        'telegram_username',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'telegram_user_id' => 'integer',
            'telegram_chat_id' => 'integer',
        ];
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function otpSent(): bool
    {
        return $this->otp_code_hash !== null && $this->telegram_user_id !== null;
    }
}
