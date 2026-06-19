<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Referral\ReferralCabinetViewData;
use App\Services\Referral\ReferralMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramBotReferralController extends Controller
{
    public function show(Request $request, ReferralMetrics $referralMetrics): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('telegram_id', (int) $data['telegram_user_id'])->first();

        if ($user === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_linked',
                'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
            ], 404);
        }

        $referralCode = (string) ($user->referral_code ?? '');
        $referralUrl = $referralCode !== ''
            ? url('/register?ref='.urlencode($referralCode))
            : url('/register');

        $quests = ReferralCabinetViewData::build($referralMetrics, $user);
        $lines = [
            'Реферальная ссылка: '.$referralUrl,
            'Почта: '.$quests->emailQuest['ratio'].' — '.$quests->emailQuest['status'],
            'Первая регистрация: '.$quests->firstRegQuest['ratio'].' — '.$quests->firstRegQuest['status'],
            'Первые оплаты друзей: '.$quests->firstPayQuest['ratio'].' — '.$quests->firstPayQuest['status'],
            'Активные подписки (до +устр.): '.$quests->active4Quest['ratio'].' — '.$quests->active4Quest['status'],
            'Активные подписки (1 мес.): '.$quests->active5Quest['ratio'].' — '.$quests->active5Quest['status'],
            'Активные подписки (3 мес.): '.$quests->active10Quest['ratio'].' — '.$quests->active10Quest['status'],
        ];

        return response()->json([
            'ok' => true,
            'referral_url' => $referralUrl,
            'lines' => $lines,
            'cabinet_url' => (string) config('telegram.cabinet_mirror_url', ''),
        ]);
    }
}
