<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramStartUtmLog extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'utm_param',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_user_id' => 'integer',
        ];
    }
}
