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
        public readonly array $activeDevicesQuest,
        public readonly array $active5Quest,
        public readonly array $active10Quest,
    ) {}

    public static function build(ReferralMetrics $metrics, User $viewer): self
    {
        $id = (int) $viewer->id;
        $emailOk = $viewer->hasVerifiedIdentity();

        $refsCount = (int) $viewer->referrals()->count();
        $activeN = $metrics->countReferralsWithActiveSubscription($id);
        $firstRegDone = $refsCount >= 1;

        $mDev = (int) config('referral.active_paid_milestone_devices', 7);
        $a5 = (int) config('referral.active_paid_milestone_one_month', 5);
        $a10 = (int) config('referral.active_paid_milestone_three_months', 10);

        $devCur = min($mDev, $activeN);
        $a5cur = min($a5, $activeN);
        $a10cur = min($a10, $activeN);

        $email = [
            'current' => $emailOk ? 1 : 0,
            'target' => 1,
            'done' => $emailOk,
            'ratio' => $emailOk ? '1/1' : '0/1',
            'bar' => $emailOk ? 100.0 : 0.0,
            'status' => $emailOk ? 'Подтверждение получено' : 'Подтвердите почту в профиле или зарегистрируйтесь в Telegram-боте',
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

        $devLeft = max(0, $mDev - $activeN);
        $activeDevices = [
            'current' => $devCur,
            'target' => $mDev,
            'done' => $activeN >= $mDev,
            'ratio' => $devCur.'/'.$mDev,
            'bar' => $mDev > 0 ? (100.0 * $devCur / $mDev) : 0.0,
            'status' => $activeN >= $mDev
                ? 'Достаточно активных подписок у приглашённых'
                : 'Активных подписок у приглашённых: '.$activeN.' из '.$mDev.' (осталось '.$devLeft.')',
        ];

        $a5left = max(0, $a5 - $activeN);
        $active5 = [
            'current' => $a5cur,
            'target' => $a5,
            'done' => $activeN >= $a5,
            'ratio' => $a5cur.'/'.$a5,
            'bar' => $a5 > 0 ? (100.0 * $a5cur / $a5) : 0.0,
            'status' => $activeN >= $a5
                ? 'Достаточно активных подписок у приглашённых'
                : 'Активных подписок у приглашённых: '.$activeN.' из '.$a5.' (осталось '.$a5left.')',
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
            $activeDevices,
            $active5,
            $active10
        );
    }
}
