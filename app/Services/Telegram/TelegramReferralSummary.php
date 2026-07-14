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

    /**
     * То же содержимое, что lines(), но с HTML-разметкой Telegram:
     * прогресс-бары, жирные заголовки, ссылка-«копировать» через <code>.
     */
    public static function html(ReferralMetrics $metrics, User $user, string $referralUrl, string $cabinetLoginUrl): string
    {
        $quests = ReferralCabinetViewData::build($metrics, $user);
        $mDev = (int) config('referral.active_paid_milestone_devices', 7);
        $a5 = (int) config('referral.active_paid_milestone_one_month', 5);
        $a10 = (int) config('referral.active_paid_milestone_three_months', 10);

        $blocks = [
            '🎁 <b>Задания и награды</b>',
            self::questBlock('1️⃣', 'Подтверждение почты', '+1 день', $quests->emailQuest),
            self::questBlock('2️⃣', 'Первая регистрация', '+1 день вам, другу 2 тест-ключа', $quests->firstRegQuest),
            self::questBlock('3️⃣', $a5.' активных оплат', '1 месяц бесплатно', $quests->active5Quest),
            self::questBlock('4️⃣', $mDev.' активных оплат', '+1 устройство навсегда', $quests->activeDevicesQuest),
            self::questBlock('5️⃣', $a10.' активных оплат', '3 месяца бесплатно', $quests->active10Quest),
            '🔗 <b>Реферальная ссылка</b> (нажмите, чтобы скопировать):'."\n"
                .'<code>'.e($referralUrl).'</code>',
            '🔐 <b>Вход в ЛК</b> (одноразовая ссылка):'."\n".e($cabinetLoginUrl),
        ];

        return implode("\n\n", $blocks);
    }

    /**
     * @param  array{ratio: string, bar: float, done: bool, status: string}  $quest
     */
    private static function questBlock(string $num, string $title, string $reward, array $quest): string
    {
        $mark = $quest['done'] ? ' ✅' : '';

        return $num.' <b>'.e($title).'</b> — '.e($reward)."\n"
            .self::progressBar((float) $quest['bar']).' '.e($quest['ratio']).$mark."\n"
            .'<i>'.e($quest['status']).'</i>';
    }

    private static function progressBar(float $percent): string
    {
        $filled = (int) round(min(100.0, max(0.0, $percent)) / 10);

        return str_repeat('▰', $filled).str_repeat('▱', 10 - $filled);
    }
}
