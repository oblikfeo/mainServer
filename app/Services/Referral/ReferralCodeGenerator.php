<?php

namespace App\Services\Referral;

use App\Models\User;
use Illuminate\Support\Str;

final class ReferralCodeGenerator
{
    public static function unique(): string
    {
        do {
            $code = strtolower(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
