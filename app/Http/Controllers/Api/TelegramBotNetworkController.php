<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramNetworkStatusService;
use Illuminate\Http\JsonResponse;

final class TelegramBotNetworkController extends Controller
{
    public function show(TelegramNetworkStatusService $networkStatus): JsonResponse
    {
        $ok = $networkStatus->allOperational();

        return response()->json([
            'ok' => true,
            'all_operational' => $ok,
        ]);
    }
}
