<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Диалог виджета поддержки на главной. public_id хранится у посетителя (localStorage) и открывает только этот диалог.
 */
class SupportChat extends Model
{
    public const STATUS_AI = 'ai';

    public const STATUS_OPERATOR = 'operator';

    protected $fillable = [
        'public_id', 'user_id', 'status', 'contact', 'ip', 'user_agent', 'page',
        'tg_card_message_id', 'handoff_at', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'handoff_at' => 'datetime',
            'last_message_at' => 'datetime',
            'tg_card_message_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportChatMessage::class);
    }

    public function isWithOperator(): bool
    {
        return $this->status === self::STATUS_OPERATOR;
    }

    /** Почта для дубля ответа оператора: указанная в чате, иначе почта аккаунта (кроме технических Telegram-адресов). */
    public function replyEmail(): ?string
    {
        $contact = trim((string) $this->contact);
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return $contact;
        }
        $email = (string) $this->user?->email;

        return $email !== '' && ! str_ends_with($email, '@telegram.nadezhda.local') ? $email : null;
    }
}
