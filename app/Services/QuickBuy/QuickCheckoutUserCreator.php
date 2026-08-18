<?php

namespace App\Services\QuickBuy;

use App\Models\PaymentOrder;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class QuickCheckoutUserCreator
{
    /**
     * @return array{0: User, 1: string}
     */
    public function create(string $email, ?string $plainPassword = null): array
    {
        $name = $this->generateDisplayName();
        $password = $plainPassword !== null && $plainPassword !== ''
            ? $plainPassword
            : Str::password(12, symbols: false);

        $user = User::query()->create([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'password' => Hash::make($password),
        ]);

        return [$user, $password];
    }

    /** Удаляет пустой аккаунт, чтобы почта снова была свободна для оплаты в 3 клика. */
    public function releaseUnpaidPlaceholder(User $user): void
    {
        if (! $user->isReleasableQuickBuyPlaceholder()) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $user->testKeys()->delete();
            Purchase::query()->where('user_id', $user->id)->delete();
            PaymentOrder::query()->where('user_id', $user->id)->delete();
            $user->delete();
        });
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
