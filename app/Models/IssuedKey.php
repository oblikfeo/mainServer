<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuedKey extends Model
{
    protected $fillable = [
        'bundle_id',
        'subscription_id',
    ];

    public static function countForBundle(string $bundleId): int
    {
        return static::query()->where('bundle_id', $bundleId)->count();
    }
}
