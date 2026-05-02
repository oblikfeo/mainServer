<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class TelegramBotMirrorController extends Controller
{
    public function show(): JsonResponse
    {
        $url = trim((string) config('telegram.cabinet_mirror_url', ''));

        return response()->json([
            'url' => $url,
        ]);
    }
}
