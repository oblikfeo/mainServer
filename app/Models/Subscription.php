<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'token',
        'fi_sub_id',
        'nl_sub_id',
        'quota_gb',
        'expiry_ms',
        'devices',
    ];

    protected function casts(): array
    {
        return [
            'quota_gb' => 'integer',
            'expiry_ms' => 'integer',
            'devices' => 'integer',
        ];
    }
}
