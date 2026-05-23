<?php

namespace App\Services\QuickBuy;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class QuickCheckoutUserCreator
{
    /**
     * @return array{0: User, 1: string}
     */
    public function create(string $email): array
    {
        $suffix = random_int(1000, 9999);
        $name = 'User'.$suffix;
        $password = Str::password(12, symbols: false);

        $user = User::query()->create([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'password' => Hash::make($password),
        ]);

        return [$user, $password];
    }

    public static function isAutogenEmail(string $email): bool
    {
        $domain = strtolower((string) config('payments.quick_buy.autogen_email_domain', 'buy.nadezhda.local'));

        return str_ends_with(strtolower(trim($email)), '@'.$domain);
    }

    private function autogenEmailDomain(): string
    {
        return (string) config('payments.quick_buy.autogen_email_domain', 'buy.nadezhda.local');
    }
}
