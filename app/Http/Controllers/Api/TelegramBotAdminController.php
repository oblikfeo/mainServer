<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramBotAdminController extends Controller
{
    private function assertAdmin(Request $request): ?JsonResponse
    {
        $data = $request->validate([
            'admin_telegram_user_id' => ['required', 'integer'],
        ]);

        $admins = config('telegram.admin_telegram_user_ids', []);
        if (! is_array($admins) || ! in_array((int) $data['admin_telegram_user_id'], $admins, true)) {
            return response()->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        return null;
    }

    public function stats(Request $request): JsonResponse
    {
        if ($err = $this->assertAdmin($request)) {
            return $err;
        }

        $nowMs = (int) (now()->getTimestamp() * 1000);

        $totalLinked = (int) User::query()->whereNotNull('telegram_id')->count();
        $blocked = (int) User::query()->whereNotNull('telegram_id')->whereNotNull('telegram_bot_blocked_at')->count();

        $activeLinked = (int) User::query()
            ->whereNotNull('telegram_id')
            ->whereNull('telegram_bot_blocked_at')
            ->whereHas('subscriptions', function ($q) use ($nowMs) {
                $q->where(function ($q2) use ($nowMs) {
                    $q2->where('expiry_ms', '<=', 0)
                        ->orWhere('expiry_ms', '>', $nowMs);
                });
            })
            ->count();

        return response()->json([
            'ok' => true,
            'total_telegram_linked' => $totalLinked,
            'active_subscriptions_linked' => $activeLinked,
            'bot_blocked' => $blocked,
        ]);
    }

    public function linkedChatIds(Request $request): JsonResponse
    {
        if ($err = $this->assertAdmin($request)) {
            return $err;
        }

        $ids = User::query()
            ->whereNotNull('telegram_id')
            ->whereNull('telegram_bot_blocked_at')
            ->pluck('telegram_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'telegram_chat_ids' => $ids,
        ]);
    }
}
