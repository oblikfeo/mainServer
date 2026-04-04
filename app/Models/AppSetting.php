<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'key';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function cacheKey(string $key): string
    {
        return 'app_setting:'.$key;
    }

    public static function getValue(string $key): ?string
    {
        return Cache::rememberForever(self::cacheKey($key), function () use ($key) {
            $row = static::query()->where('key', $key)->first();

            return $row === null ? null : $row->value;
        });
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget(self::cacheKey($key));
    }

    public static function forgetKey(string $key): void
    {
        static::query()->where('key', $key)->delete();
        Cache::forget(self::cacheKey($key));
    }
}
