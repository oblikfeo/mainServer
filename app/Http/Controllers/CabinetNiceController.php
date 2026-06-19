<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Referral\ReferralCabinetViewData;
use App\Services\Referral\ReferralMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CabinetNiceController extends Controller
{
    public function show(Request $request, ReferralMetrics $referralMetrics): View
    {
        /** @var User $user */
        $user = $request->user();
        $referralCode = (string) ($user->referral_code ?? '');
        $referralLink = $referralCode !== ''
            ? url('/register?ref='.urlencode($referralCode))
            : url('/register');

        $quests = ReferralCabinetViewData::build($referralMetrics, $user);

        $questList = [
            [
                'key' => 'email',
                'num' => 1,
                'title' => 'Подтверждение почты',
                'subtitle' => 'Доступ к наградам и важным уведомлениям',
                'data' => $quests->emailQuest,
                'reward_you' => '+1 день к подписке',
                'reward_friend' => null,
                'feature' => null,
                'steps' => [
                    'Откройте раздел «Профиль» в кабинете.',
                    'Нажмите «Подтвердить почту» рядом с адресом.',
                    'Введите 6-значный код, который пришёл на email.',
                ],
                'cta' => [
                    'label' => 'Перейти в профиль',
                    'href' => route('cabinet.profile'),
                    'primary' => true,
                ],
                'done_cta' => [
                    'label' => 'Почта подтверждена',
                    'href' => null,
                ],
            ],
            [
                'key' => 'first_reg',
                'num' => 2,
                'title' => 'Первая регистрация',
                'subtitle' => 'Позовите друга по своей ссылке',
                'data' => $quests->firstRegQuest,
                'reward_you' => '+1 день к подписке',
                'reward_friend' => '2 тест-ключа по 8 ч',
                'feature' => null,
                'steps' => [
                    'Скопируйте свою реферальную ссылку (ниже или в разделе «Реферальная программа»).',
                    'Отправьте её другу любым удобным способом.',
                    'Как только друг зарегистрируется — задание выполнится автоматически.',
                ],
                'cta' => [
                    'label' => 'Скопировать ссылку',
                    'href' => null,
                    'copy' => $referralLink,
                    'primary' => true,
                ],
                'cta_secondary' => [
                    'label' => 'Открыть реферальную',
                    'href' => route('cabinet.referral'),
                ],
                'done_cta' => [
                    'label' => 'Задание выполнено',
                    'href' => null,
                ],
            ],
            [
                'key' => 'first_pay',
                'num' => 3,
                'title' => 'Первая оплата',
                'subtitle' => 'Трое приглашённых с первой оплатой',
                'data' => $quests->firstPayQuest,
                'reward_you' => '+7 дней к подписке',
                'reward_friend' => '+7 дней к подписке',
                'feature' => null,
                'steps' => [
                    'Приглашайте друзей по своей реферальной ссылке.',
                    'После первой оплаты каждого — на ваш счёт начислится +7 дней.',
                    'Другу тоже добавится +7 дней к его подписке.',
                ],
                'cta' => [
                    'label' => 'Пригласить друзей',
                    'href' => route('cabinet.referral'),
                    'primary' => true,
                ],
                'done_cta' => [
                    'label' => 'Задание выполнено',
                    'href' => null,
                ],
            ],
            [
                'key' => 'active4',
                'num' => 4,
                'title' => '4 активные оплаты',
                'subtitle' => 'Четверо друзей с активной подпиской',
                'data' => $quests->active4Quest,
                'reward_you' => null,
                'reward_friend' => null,
                'feature' => [
                    'title' => '+1 устройство',
                    'sub' => 'навсегда',
                    'tag' => 'эксклюзив',
                ],
                'steps' => [
                    'Приглашайте друзей и помогайте им подключаться.',
                    'Считаются только друзья с активной подпиской на текущий момент.',
                    'По достижении 4 — лимит устройств увеличится навсегда.',
                ],
                'cta' => [
                    'label' => 'Пригласить друзей',
                    'href' => route('cabinet.referral'),
                    'primary' => true,
                ],
                'done_cta' => [
                    'label' => 'Лимит устройств увеличен',
                    'href' => null,
                ],
            ],
            [
                'key' => 'active5',
                'num' => 5,
                'title' => '5 активных оплат',
                'subtitle' => 'Пятеро друзей с активной подпиской',
                'data' => $quests->active5Quest,
                'reward_you' => '1 месяц бесплатно',
                'reward_friend' => null,
                'feature' => [
                    'title' => '1 месяц бесплатно',
                    'sub' => 'к вашей подписке',
                    'tag' => 'эксклюзив',
                ],
                'steps' => [
                    'Приглашайте друзей и помогайте им подключаться.',
                    'Считаются только друзья с активной подпиской на текущий момент.',
                    'По достижении 5 — к подписке добавится месяц бесплатно.',
                ],
                'cta' => [
                    'label' => 'Пригласить друзей',
                    'href' => route('cabinet.referral'),
                    'primary' => true,
                ],
                'done_cta' => [
                    'label' => 'Месяц начислен',
                    'href' => null,
                ],
            ],
            [
                'key' => 'active10',
                'num' => 6,
                'title' => '10 активных оплат',
                'subtitle' => 'Десять друзей с активной подпиской',
                'data' => $quests->active10Quest,
                'reward_you' => '3 месяца бесплатно',
                'reward_friend' => null,
                'feature' => [
                    'title' => '3 месяца бесплатно',
                    'sub' => 'к вашей подписке',
                    'tag' => 'эксклюзив',
                ],
                'steps' => [
                    'Приглашайте друзей и помогайте им оставаться с сервисом.',
                    'Считаются друзья с активной подпиской на момент проверки.',
                    'По достижении 10 — к подписке добавится три месяца бесплатно.',
                ],
                'cta' => [
                    'label' => 'Пригласить друзей',
                    'href' => route('cabinet.referral'),
                    'primary' => true,
                ],
                'done_cta' => [
                    'label' => 'Три месяца начислены',
                    'href' => null,
                ],
            ],
        ];

        $totalCount = count($questList);
        $doneCount = 0;
        $progressSum = 0.0;
        foreach ($questList as $q) {
            if (($q['data']['done'] ?? false) === true) {
                $doneCount++;
            }
            $progressSum += (float) ($q['data']['bar'] ?? 0.0);
        }
        $overallPercent = $totalCount > 0 ? (int) round($progressSum / $totalCount) : 0;

        $rewards = [];
        if ($quests->emailQuest['done']) {
            $rewards[] = ['icon' => '✉', 'label' => 'Почта подтверждена', 'sub' => 'доступ к наградам'];
        }
        if ($quests->firstRegQuest['done']) {
            $rewards[] = ['icon' => '+1', 'label' => '+1 день к подписке', 'sub' => 'первая регистрация'];
        }
        if ($quests->firstPayQuest['done']) {
            $rewards[] = ['icon' => '+7', 'label' => '+7 дней к подписке', 'sub' => 'трое с первой оплатой'];
        }
        if ($quests->active4Quest['done']) {
            $rewards[] = ['icon' => '★', 'label' => '+1 устройство навсегда', 'sub' => '4 активные оплаты'];
        }
        if ($quests->active5Quest['done']) {
            $rewards[] = ['icon' => '30', 'label' => '1 месяц бесплатно', 'sub' => '5 активных оплат'];
        }
        if ($quests->active10Quest['done']) {
            $rewards[] = ['icon' => '90', 'label' => '3 месяца бесплатно', 'sub' => '10 активных оплат'];
        }

        return view('cabinet.nice.index', [
            'referralLink' => $referralLink,
            'questList' => $questList,
            'doneCount' => $doneCount,
            'totalCount' => $totalCount,
            'overallPercent' => $overallPercent,
            'rewards' => $rewards,
        ]);
    }
}
