<?php

use App\Http\Controllers\Api\TelegramLinkClaimController;
use Illuminate\Support\Facades\Route;

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/link/claim', [TelegramLinkClaimController::class, 'claim'])
    ->name('api.telegram.link.claim');
