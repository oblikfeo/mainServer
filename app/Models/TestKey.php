<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TestKey extends Model
{
    protected $fillable = [
        'user_id',
        'client_uuid',
        'panel_email',
        'panel_sub_id',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revoked_reason',
        'panel_deleted_at',
        'vless_url',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'panel_deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        $exp = $this->expires_at instanceof Carbon ? $this->expires_at : Carbon::parse($this->expires_at);

        return $exp->isPast();
    }
}

