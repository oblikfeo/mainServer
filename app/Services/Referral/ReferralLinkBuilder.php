<?php

namespace App\Services\Referral;

use App\Models\User;

final class ReferralLinkBuilder
{
    public function forUser(User $user): string
    {
        $code = (string) ($user->referral_code ?? '');

        return $code !== ''
            ? url('/register?ref='.urlencode($code))
            : url('/register');
    }
}
