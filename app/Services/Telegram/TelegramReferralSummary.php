<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\Referral\ReferralCabinetViewData;
use App\Services\Referral\ReferralMetrics;

/**
 * Тексты «Мои бонусы» в Telegram — в том же смысле, что задания в cabinet/nice.
 */
final class TelegramReferralSummary
{
    /**
     * @return list<string>
     */
    public static function lines(ReferralMetrics $metrics, User $user, string $referralUrl, string $cabinetLoginUrl): array
    {
        $quests = ReferralCabinetViewData::build($metrics, $user);
        $mDev = (int) config('referral.active_paid_milestone_devices', 7);
        $a5 = (int) config('referral.active_paid_milestone_one_month', 5);
        $a10 = (int) config('referral.active_paid_milestone_three_months', 10);

        return [
            'Задания и награды:',
            '',
            '1. Подтверждение почты — '.$quests->emailQuest['ratio'].' (+1 день)',
            '   '.$quests->emailQuest['status'],
            '',
            '2. Первая регистрация — '.$quests->firstRegQuest['ratio'].' (+1 день вам, другу 2 тест-ключа)',
            '   '.$quests->firstRegQuest['status'],
            '',
            '3. '.$a5.' активных оплат — '.$quests->active5Quest['ratio'].' (1 месяц бесплатно)',
            '   '.$quests->active5Quest['status'],
            '',
            '4. '.$mDev.' активных оплат — '.$quests->activeDevicesQuest['ratio'].' (+1 устройство навсегда)',
            '   '.$quests->activeDevicesQuest['status'],
            '',
            '5. '.$a10.' активных оплат — '.$quests->active10Quest['ratio'].' (3 месяца бесплатно)',
            '   '.$quests->active10Quest['status'],
            '',
            'Реферальная ссылка:',
            $referralUrl,
            '',
            'Вход в ЛК (одноразовая ссылка):',
            $cabinetLoginUrl,
        ];
    }
}
