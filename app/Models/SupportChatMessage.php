<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatMessage extends Model
{
    protected $fillable = ['support_chat_id', 'role', 'content', 'tg_message_id', 'operator_name'];

    protected function casts(): array
    {
        return ['tg_message_id' => 'integer'];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(SupportChat::class, 'support_chat_id');
    }

    /** @return array{id: int, role: string, content: string, at: string} */
    public function toWidget(): array
    {
        return [
            'id' => (int) $this->id,
            'role' => (string) $this->role,
            'content' => (string) $this->content,
            'at' => $this->created_at?->timezone((string) config('app.timezone'))->format('H:i') ?? '',
        ];
    }
}
