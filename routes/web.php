<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionSettingsController;
use App\Http\Controllers\Admin\TestKeysController;
use App\Http\Controllers\CabinetBonusesController;
use App\Http\Controllers\CabinetCreatePaymentLinkController;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\CabinetNiceController;
use App\Http\Controllers\CabinetReferralController;
use App\Http\Controllers\CabinetPaymentController;
use App\Http\Controllers\CabinetRenewalController;
use App\Http\Controllers\CabinetSettingsController;
use App\Http\Controllers\CabinetTestKeysController;
use App\Http\Controllers\EmailCodeVerificationController;
use App\Http\Controllers\PaymentSuccessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseHistoryController;
use App\Http\Controllers\QuickCheckoutController;
use App\Http\Controllers\SubscriptionFeedController;
use App\Http\Controllers\TelegramLinkController;
use App\Http\Controllers\TestSubscriptionController;
use App\Http\Controllers\TestSubscriptionFeedController;
use App\Http\Controllers\WataWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('views2::home');
})->name('home');

/** Бывшая тестовая вёрстка: редирект на главную */
Route::redirect('/test', '/', 301)->name('home.test');

Route::get('/agreement', function () {
    return view('agreement');
})->name('agreement');

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');

Route::get('/terms', function () {
    return view('terms');
})->name('terms');

Route::get('/privacy-policy', function () {
    return view('privacy');
});

Route::view('/oshibka', 'oshibka')->name('payment.failure');

Route::get('/spasibo', PaymentSuccessController::class)->name('payment.success');

Route::get('/buy', [QuickCheckoutController::class, 'show'])->name('quick_buy.show');
Route::post('/buy/pay', [QuickCheckoutController::class, 'pay'])
    ->middleware('throttle:15,1')
    ->name('quick_buy.pay');
Route::get('/buy/status/{orderId}', [QuickCheckoutController::class, 'status'])
    ->middleware('throttle:60,1')
    ->where('orderId', 'ord_[A-Za-z0-9_-]+')
    ->name('quick_buy.status');
Route::get('/buy/done/{claimToken}', [QuickCheckoutController::class, 'done'])
    ->where('claimToken', '[A-Za-z0-9]{32,64}')
    ->name('quick_buy.done');

Route::post('/payments/wata/webhook', WataWebhookController::class)->name('payments.wata.webhook');

Route::get('/sub/{token}', [SubscriptionFeedController::class, 'show'])
    ->name('subscription.feed');

Route::get('/test-sub/{token}', [TestSubscriptionFeedController::class, 'show'])
    ->where('token', '[A-Za-z0-9_-]{8,80}')
    ->name('test-subscription.feed');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [CabinetController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/profile', [ProfileController::class, 'edit'])->name('cabinet.profile');
    Route::get('/dashboard/referral', [CabinetReferralController::class, 'show'])->name('cabinet.referral');
    Route::get('/dashboard/bonuses', CabinetBonusesController::class)->name('cabinet.bonuses');
    Route::get('/nice', [CabinetNiceController::class, 'show'])->name('cabinet.nice');
    Route::patch('/dashboard/profile', [ProfileController::class, 'update'])->name('cabinet.profile.update');
    Route::delete('/dashboard/profile', [ProfileController::class, 'destroy'])->name('cabinet.profile.destroy');
    Route::get('/dashboard/purchases', [PurchaseHistoryController::class, 'index'])->name('cabinet.purchases');
    Route::get('/dashboard/payment', CabinetPaymentController::class)->name('cabinet.payment');
    Route::get('/dashboard/renewal', CabinetRenewalController::class)->name('cabinet.renewal');
    Route::post('/dashboard/payment/link', CabinetCreatePaymentLinkController::class)
        ->middleware('throttle:20,1')
        ->name('cabinet.payment.link');
    Route::get('/dashboard/settings', [CabinetSettingsController::class, 'index'])->name('cabinet.settings');
    Route::post('/dashboard/settings/subscriptions/{subscription}/devices/detach', [CabinetSettingsController::class, 'detachDevice'])
        ->middleware('throttle:30,1')
        ->name('cabinet.settings.device.detach');
    Route::post('/dashboard/settings/subscriptions/{subscription}/devices/clear', [CabinetSettingsController::class, 'clearAllDevices'])
        ->middleware('throttle:10,1')
        ->name('cabinet.settings.devices.clear');
    Route::post('/dashboard/settings/test-keys/{testKey}/devices/detach', [CabinetSettingsController::class, 'detachTestKeyDevice'])
        ->middleware('throttle:30,1')
        ->name('cabinet.settings.test_key.device.detach');
    Route::post('/dashboard/settings/test-keys/{testKey}/devices/clear', [CabinetSettingsController::class, 'clearAllTestKeyDevices'])
        ->middleware('throttle:10,1')
        ->name('cabinet.settings.test_key.devices.clear');

    Route::post('/dashboard/test-subscription', [TestSubscriptionController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('cabinet.test_subscription');

    Route::post('/dashboard/test-keys', [CabinetTestKeysController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('cabinet.test_keys.store');

    Route::post('/dashboard/email/verify-code/send', [EmailCodeVerificationController::class, 'send'])
        ->name('cabinet.email_code.send');
    Route::post('/dashboard/email/verify-code/check', [EmailCodeVerificationController::class, 'verify'])
        ->name('cabinet.email_code.verify');

    Route::post('/dashboard/profile/telegram/start', [TelegramLinkController::class, 'start'])
        ->middleware('throttle:10,1')
        ->name('cabinet.telegram.start');
    Route::post('/dashboard/profile/telegram/unlink', [TelegramLinkController::class, 'unlink'])
        ->middleware('throttle:10,1')
        ->name('cabinet.telegram.unlink');

    Route::redirect('/profile', '/dashboard/profile')->name('profile.edit');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('admin.guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('login.store');
    });

    Route::middleware('admin')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('referral', [ReferralController::class, 'index'])->name('referral');
        Route::get('/servers', [DashboardController::class, 'servers'])->name('servers');
        Route::get('/report', [ReportController::class, 'index'])->name('report');
        Route::get('/payments', [PaymentsController::class, 'index'])->name('payments');
        Route::get('/test-keys', [TestKeysController::class, 'index'])->name('test_keys');
        Route::post('/test-keys', [TestKeysController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('test_keys.store');
        Route::post('/test-keys/cleanup', [TestKeysController::class, 'cleanup'])
            ->middleware('throttle:10,1')
            ->name('test_keys.cleanup');
        Route::post('/test-keys/{subscription}/revoke', [TestKeysController::class, 'revoke'])
            ->middleware('throttle:60,1')
            ->name('test_keys.revoke');
        Route::get('/subscription/create', [SubscriptionController::class, 'create'])->name('subscription.create');
        Route::get('/subscription/routing', [SubscriptionSettingsController::class, 'editRouting'])->name('subscription.routing');
        Route::post('/subscription/routing', [SubscriptionSettingsController::class, 'updateRouting'])->name('subscription.routing.update');
        Route::get('/subscription/announce', [SubscriptionSettingsController::class, 'editAnnounce'])->name('subscription.announce');
        Route::post('/subscription/announce', [SubscriptionSettingsController::class, 'updateAnnounce'])->name('subscription.announce.update');
        Route::post('/subscription', [SubscriptionController::class, 'store'])
            ->middleware('throttle:8,1')
            ->name('subscription.store');
        Route::post('/subscription/cool', [SubscriptionController::class, 'storeCool'])
            ->middleware('throttle:8,1')
            ->name('subscription.store_cool');
        Route::get('/subscription/cool', function () {
            return redirect()->route('admin.subscription.create');
        })->name('subscription.cool');
        Route::post('/subscription/{subscription}/owner', [SubscriptionController::class, 'attachOwner'])
            ->middleware('throttle:60,1')
            ->name('subscription.owner');
        Route::post('/subscription/{subscription}/destroy', [SubscriptionController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('subscription.destroy');
        Route::get('/subscription/result/{subscription}', [SubscriptionController::class, 'show'])
            ->name('subscription.show');
    });
});

require __DIR__.'/auth.php';
