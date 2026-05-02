<?php

use App\Http\Controllers\Api\TelegramBotAdminController;
use App\Http\Controllers\Api\TelegramBotMarkBlockedController;
use App\Http\Controllers\Api\TelegramBotMirrorController;
use App\Http\Controllers\Api\TelegramBotNetworkController;
use App\Http\Controllers\Api\TelegramBotReferralController;
use App\Http\Controllers\Api\TelegramLinkClaimController;
use App\Http\Controllers\Api\TelegramStartUtmController;
use Illuminate\Support\Facades\Route;

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/link/claim', [TelegramLinkClaimController::class, 'claim'])
    ->name('api.telegram.link.claim');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/start/utm', [TelegramStartUtmController::class, 'store'])
    ->name('api.telegram.start.utm');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->get('/internal/telegram/bot/mirror', [TelegramBotMirrorController::class, 'show'])
    ->name('api.telegram.bot.mirror');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/referral-summary', [TelegramBotReferralController::class, 'show'])
    ->name('api.telegram.bot.referral');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->get('/internal/telegram/bot/network-status', [TelegramBotNetworkController::class, 'show'])
    ->name('api.telegram.bot.network');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/mark-blocked', [TelegramBotMarkBlockedController::class, 'store'])
    ->name('api.telegram.bot.mark_blocked');

Route::middleware(['telegram.link.internal', 'throttle:30,1'])
    ->post('/internal/telegram/admin/stats', [TelegramBotAdminController::class, 'stats'])
    ->name('api.telegram.admin.stats');

Route::middleware(['telegram.link.internal', 'throttle:30,1'])
    ->post('/internal/telegram/admin/linked-chat-ids', [TelegramBotAdminController::class, 'linkedChatIds'])
    ->name('api.telegram.admin.linked_chat_ids');
