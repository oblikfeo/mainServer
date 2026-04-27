<?php

namespace App\Services\Referral;

use App\Models\ReferralGrant;
use App\Models\User;

/**
 * Подсчёты прогресса и списка рефералов для кабинета.
 */
final class ReferralMetrics
{
    public function nowMs(): int
    {
        return (int) (now()->getTimestamp() * 1000);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    public function referralsQuery(User $referrer)
    {
        return User::query()
            ->where('referred_by', $referrer->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * Рефералы, у которых есть хотя бы одна оплата (для этапа «Первая оплата» N/3).
     */
    public function countReferralsWithAnyPurchase(int $referrerId): int
    {
        return (int) User::query()
            ->where('referred_by', $referrerId)
            ->whereHas('purchases')
            ->count();
    }

    /**
     * Рефералы с «активной» подпиской (срок в будущем либо без срока).
     */
    public function countReferralsWithActiveSubscription(int $referrerId): int
    {
        $nowMs = $this->nowMs();

        return (int) User::query()
            ->where('referred_by', $referrerId)
            ->whereHas('subscriptions', function ($q) use ($nowMs) {
                $q->where(function ($q2) use ($nowMs) {
                    $q2->where('expiry_ms', '<=', 0)
                        ->orWhere('expiry_ms', '>', $nowMs);
                });
            })
            ->count();
    }

    public function hasAnyGrantForReferee(int $refereeId): bool
    {
        return ReferralGrant::query()->where('referee_user_id', $refereeId)->exists();
    }

    /**
     * @return list<array{name: string, email_masked: string, status: string, status_kind: string}>
     */
    public function referralHistoryCards(User $referrer): array
    {
        $out = [];
        $refs = $this->referralsQuery($referrer);
        $nowMs = $this->nowMs();

        foreach ($refs as $r) {
            $hasPurchase = $r->purchases()->exists();
            $activeSub = $r->subscriptions()
                ->where(function ($q) use ($nowMs) {
                    $q->where('expiry_ms', '<=', 0)
                        ->orWhere('expiry_ms', '>', $nowMs);
                })
                ->exists();

            $granted = $this->hasAnyGrantForReferee((int) $r->id);
            if ($granted) {
                $status = 'Бонус начислен';
                $kind = 'bonus';
            } elseif ($hasPurchase || $activeSub) {
                $status = 'Оплатил';
                $kind = 'ok';
            } else {
                $status = 'Ожидание оплаты';
                $kind = 'wait';
            }

            $out[] = [
                'name' => (string) $r->name,
                'email_masked' => $this->maskEmail((string) $r->email),
                'status' => $status,
                'status_kind' => $kind,
            ];
        }

        return $out;
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return '—';
        }
        [$local, $host] = explode('@', $email, 2);
        $ll = $local;
        if (strlen($ll) <= 1) {
            $masked = $ll.'***';
        } else {
            $masked = $ll[0].'***';
        }

        $hostParts = explode('.', $host, 2);
        $h0 = (string) ($hostParts[0] ?? '');
        if (strlen($h0) > 0) {
            $maskedHost = $h0[0].'***';
            if (isset($hostParts[1]) && is_string($hostParts[1])) {
                $maskedHost .= '.'.$hostParts[1];
            }
        } else {
            $maskedHost = '***';
        }

        return $masked.'@'.$maskedHost;
    }
}
