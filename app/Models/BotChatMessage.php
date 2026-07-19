<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Одна реплика диалога клиента с ИИ-ассистентом в Telegram-боте
 * (кнопка «Поддержка»). Роль — user (клиент) или assistant (ИИ).
 */
class BotChatMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'telegram_user_id',
        'telegram_username',
        'user_id',
        'role',
        'content',
        'handoff',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_user_id' => 'integer',
            'user_id' => 'integer',
            'handoff' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
