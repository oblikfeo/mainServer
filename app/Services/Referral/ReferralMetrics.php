<?php

namespace App\Services\Referral;

use App\Models\PaymentOrder;
use App\Models\ReferralGrant;
use App\Models\Subscription;
use App\Models\TestKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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
     * Рефералы, у которых есть хотя бы одна оплата.
     */
    public function countReferralsWithAnyPurchase(int $referrerId): int
    {
        return (int) User::query()
            ->where('referred_by', $referrerId)
            ->whereHas('purchases')
            ->count();
    }

    /**
     * Реферал «оплатил» для админки: есть покупка, оплаченный заказ WATA или активная платная подписка (не trial).
     */
    public function referralIsPaid(User $referee): bool
    {
        if ($referee->purchases()->exists()) {
            return true;
        }

        if (PaymentOrder::query()->where('user_id', $referee->id)->where('status', 'paid')->exists()) {
            return true;
        }

        return $referee->hasActiveNonTrialSubscription();
    }

    public function countReferralsPaid(int $referrerId): int
    {
        $q = User::query()->where('referred_by', $referrerId);
        $this->applyReferralPaidScope($q);

        return (int) $q->count();
    }

    /** @param Builder<User> $query */
    public function applyReferralPaidScope(Builder $query): void
    {
        $nowMs = $this->nowMs();
        $query->where(function (Builder $q) use ($nowMs) {
            $q->whereHas('purchases')
                ->orWhereHas('paymentOrders', fn (Builder $po) => $po->where('status', 'paid'))
                ->orWhereHas('subscriptions', function (Builder $s) use ($nowMs) {
                    $s->where('is_trial', false)
                        ->where(function (Builder $s2) use ($nowMs) {
                            $s2->where('expiry_ms', '<=', 0)
                                ->orWhere('expiry_ms', '>', $nowMs);
                        });
                });
        });
    }

    /** Сколько тестовых ключей/пробных подписок выдали рефералам этого реферера. */
    public function countReferralTestIssuances(int $referrerId): int
    {
        $refereeIds = User::query()
            ->where('referred_by', $referrerId)
            ->pluck('id');

        if ($refereeIds->isEmpty()) {
            return 0;
        }

        $trialCount = (int) Subscription::query()
            ->whereIn('user_id', $refereeIds)
            ->where('is_trial', true)
            ->count();

        $legacyCount = (int) TestKey::query()
            ->whereIn('user_id', $refereeIds)
            ->count();

        return $trialCount + $legacyCount;
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
