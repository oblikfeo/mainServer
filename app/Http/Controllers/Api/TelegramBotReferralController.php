<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Referral\ReferralMetrics;
use App\Services\Telegram\TelegramCabinetAccessService;
use App\Services\Telegram\TelegramReferralSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramBotReferralController extends Controller
{
    public function show(
        Request $request,
        ReferralMetrics $referralMetrics,
        TelegramCabinetAccessService $cabinetAccess,
    ): JsonResponse {
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

        $cabinetLoginUrl = $cabinetAccess->loginUrlForUser($user);

        return response()->json([
            'ok' => true,
            'referral_url' => $referralUrl,
            'lines' => TelegramReferralSummary::lines($referralMetrics, $user, $referralUrl, $cabinetLoginUrl),
            'cabinet_url' => $cabinetLoginUrl,
        ]);
    }
}
