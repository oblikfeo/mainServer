<?php

use App\Http\Controllers\Api\TelegramBotAdminController;
use App\Http\Controllers\Api\TelegramBotChatController;
use App\Http\Controllers\Api\TelegramBotDevicesController;
use App\Http\Controllers\Api\TelegramBotPaymentController;
use App\Http\Controllers\Api\TelegramBotMarkBlockedController;
use App\Http\Controllers\Api\TelegramBotMirrorController;
use App\Http\Controllers\Api\TelegramBotNetworkController;
use App\Http\Controllers\Api\TelegramBotReferralController;
use App\Http\Controllers\Api\TelegramBotRegisterController;
use App\Http\Controllers\Api\TelegramBotStatusController;
use App\Http\Controllers\Api\TelegramBotTrialController;
use App\Http\Controllers\Api\TelegramLinkClaimController;
use App\Http\Controllers\Api\TelegramStartUtmController;
use Illuminate\Support\Facades\Route;

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/link/claim', [TelegramLinkClaimController::class, 'claim'])
    ->name('api.telegram.link.claim');

Route::middleware(['telegram.link.internal', 'throttle:60,1'])
    ->post('/internal/telegram/register', [TelegramBotRegisterController::class, 'store'])
    ->name('api.telegram.register');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/status', [TelegramBotStatusController::class, 'show'])
    ->name('api.telegram.bot.status');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/start/utm', [TelegramStartUtmController::class, 'store'])
    ->name('api.telegram.start.utm');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/mirror', [TelegramBotMirrorController::class, 'show'])
    ->name('api.telegram.bot.mirror');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/referral-summary', [TelegramBotReferralController::class, 'show'])
    ->name('api.telegram.bot.referral');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->get('/internal/telegram/bot/network-status', [TelegramBotNetworkController::class, 'show'])
    ->name('api.telegram.bot.network');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/devices', [TelegramBotDevicesController::class, 'list'])
    ->name('api.telegram.bot.devices.list');

Route::middleware(['telegram.link.internal', 'throttle:60,1'])
    ->post('/internal/telegram/bot/devices/detach', [TelegramBotDevicesController::class, 'detach'])
    ->name('api.telegram.bot.devices.detach');

Route::middleware(['telegram.link.internal', 'throttle:60,1'])
    ->post('/internal/telegram/bot/devices/clear', [TelegramBotDevicesController::class, 'clear'])
    ->name('api.telegram.bot.devices.clear');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->get('/internal/telegram/bot/payment/catalog', [TelegramBotPaymentController::class, 'catalog'])
    ->name('api.telegram.bot.payment.catalog');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/payment/subscriptions', [TelegramBotPaymentController::class, 'subscriptions'])
    ->name('api.telegram.bot.payment.subscriptions');

Route::middleware(['telegram.link.internal', 'throttle:60,1'])
    ->post('/internal/telegram/bot/payment/create', [TelegramBotPaymentController::class, 'create'])
    ->name('api.telegram.bot.payment.create');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/payment/status', [TelegramBotPaymentController::class, 'status'])
    ->name('api.telegram.bot.payment.status');

Route::middleware(['telegram.link.internal', 'throttle:30,1'])
    ->post('/internal/telegram/bot/trial/issue', [TelegramBotTrialController::class, 'issue'])
    ->name('api.telegram.bot.trial.issue');

Route::middleware(['telegram.link.internal', 'throttle:30,1'])
    ->post('/internal/telegram/bot/chat', [TelegramBotChatController::class, 'reply'])
    ->name('api.telegram.bot.chat');

Route::middleware(['telegram.link.internal', 'throttle:120,1'])
    ->post('/internal/telegram/bot/mark-blocked', [TelegramBotMarkBlockedController::class, 'store'])
    ->name('api.telegram.bot.mark_blocked');

Route::middleware(['telegram.link.internal', 'throttle:30,1'])
    ->post('/internal/telegram/admin/stats', [TelegramBotAdminController::class, 'stats'])
    ->name('api.telegram.admin.stats');

Route::middleware(['telegram.link.internal', 'throttle:30,1'])
    ->post('/internal/telegram/admin/linked-chat-ids', [TelegramBotAdminController::class, 'linkedChatIds'])
    ->name('api.telegram.admin.linked_chat_ids');
