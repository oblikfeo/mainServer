<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionSettingsController;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseHistoryController;
use App\Http\Controllers\SubscriptionFeedController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/agreement', function () {
    return view('agreement');
})->name('agreement');

Route::view('/spasibo', 'spasibo')->name('payment.success');
Route::view('/oshibka', 'oshibka')->name('payment.failure');

Route::get('/sub/{token}', [SubscriptionFeedController::class, 'show'])
    ->name('subscription.feed');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [CabinetController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/profile', [ProfileController::class, 'edit'])->name('cabinet.profile');
    Route::patch('/dashboard/profile', [ProfileController::class, 'update'])->name('cabinet.profile.update');
    Route::delete('/dashboard/profile', [ProfileController::class, 'destroy'])->name('cabinet.profile.destroy');
    Route::get('/dashboard/purchases', [PurchaseHistoryController::class, 'index'])->name('cabinet.purchases');

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
        Route::get('/servers', [DashboardController::class, 'servers'])->name('servers');
        Route::get('/report', [ReportController::class, 'index'])->name('report');
        Route::get('/subscription/create', [SubscriptionController::class, 'create'])->name('subscription.create');
        Route::get('/subscription/settings', [SubscriptionSettingsController::class, 'edit'])->name('subscription.settings');
        Route::post('/subscription/settings', [SubscriptionSettingsController::class, 'update'])->name('subscription.settings.update');
        Route::post('/subscription', [SubscriptionController::class, 'store'])
            ->middleware('throttle:8,1')
            ->name('subscription.store');
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
