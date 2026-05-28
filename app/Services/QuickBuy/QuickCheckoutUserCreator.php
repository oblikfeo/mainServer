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
        $name = $this->generateDisplayName();
        $password = Str::password(12, symbols: false);

        $user = User::query()->create([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'password' => Hash::make($password),
        ]);

        return [$user, $password];
    }

    /** Формат User1234 — пример; каждый раз свой случайный суффикс. */
    private function generateDisplayName(): string
    {
        for ($i = 0; $i < 30; $i++) {
            $name = 'User'.random_int(1000, 999999);
            if (! User::query()->where('name', $name)->exists()) {
                return $name;
            }
        }

        return 'User'.Str::lower(Str::random(8));
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
