<?php

namespace App\Services\Referral;

use App\Models\User;

/**
 * DTO-структура для @include в blade (массивы с ключами).
 */
final class ReferralCabinetViewData
{
    public function __construct(
        public readonly array $emailQuest,
        public readonly array $firstRegQuest,
        public readonly array $firstPayQuest,
        public readonly array $active4Quest,
        public readonly array $active10Quest,
    ) {}

    public static function build(ReferralMetrics $metrics, User $viewer): self
    {
        $id = (int) $viewer->id;
        $emailOk = $viewer->hasVerifiedEmail();

        $refsCount = (int) $viewer->referrals()->count();
        $withPay = $metrics->countReferralsWithAnyPurchase($id);
        $activeN = $metrics->countReferralsWithActiveSubscription($id);
        $firstRegDone = $refsCount >= 1;

        $m3 = (int) config('referral.first_payment_referees_target', 3);
        $a4 = (int) config('referral.active_paid_milestone_devices', 4);
        $a10 = (int) config('referral.active_paid_milestone_traffic', 10);

        $fpCurrent = min($m3, $withPay);
        $a4cur = min($a4, $activeN);
        $a10cur = min($a10, $activeN);

        $email = [
            'current' => $emailOk ? 1 : 0,
            'target' => 1,
            'done' => $emailOk,
            'ratio' => $emailOk ? '1/1' : '0/1',
            'bar' => $emailOk ? 100.0 : 0.0,
            'status' => $emailOk ? 'Почта подтверждена' : 'Подтвердите почту в профиле',
        ];

        $fr = [
            'current' => min(1, $refsCount),
            'target' => 1,
            'done' => $firstRegDone,
            'ratio' => (min(1, $refsCount)).'/1',
            'bar' => $firstRegDone ? 100.0 : 0.0,
            'status' => $firstRegDone
                ? 'Первая регистрация по ссылке получена'
                : 'Ждём первую регистрацию по ссылке',
        ];

        $fpLeft = max(0, $m3 - $withPay);
        $firstPay = [
            'current' => $fpCurrent,
            'target' => $m3,
            'done' => $withPay >= $m3,
            'ratio' => $fpCurrent.'/'.$m3,
            'bar' => $m3 > 0 ? (100.0 * $fpCurrent / $m3) : 0.0,
            'status' => $withPay >= $m3
                ? 'Полный набор: у трёх приглашённых была первая оплата'
                : 'Первая оплата у приглашённых: '.$withPay.' из '.$m3,
        ];

        $a4left = max(0, $a4 - $activeN);
        $active4 = [
            'current' => $a4cur,
            'target' => $a4,
            'done' => $activeN >= $a4,
            'ratio' => $a4cur.'/'.$a4,
            'bar' => $a4 > 0 ? (100.0 * $a4cur / $a4) : 0.0,
            'status' => $activeN >= $a4
                ? 'Достаточно активных подписок у приглашённых'
                : 'Активных подписок у приглашённых: '.$activeN.' из '.$a4.' (осталось '.$a4left.')',
        ];

        $a10left = max(0, $a10 - $activeN);
        $active10 = [
            'current' => $a10cur,
            'target' => $a10,
            'done' => $activeN >= $a10,
            'ratio' => $a10cur.'/'.$a10,
            'bar' => $a10 > 0 ? (100.0 * $a10cur / $a10) : 0.0,
            'status' => $activeN >= $a10
                ? 'Достаточно активных подписок у приглашённых'
                : 'Активных подписок у приглашённых: '.$activeN.' из '.$a10.' (осталось '.$a10left.')',
        ];

        return new self(
            $email,
            $fr,
            $firstPay,
            $active4,
            $active10
        );
    }
}
