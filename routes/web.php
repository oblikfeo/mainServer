<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionFeedController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sub/{token}', [SubscriptionFeedController::class, 'show'])
    ->name('subscription.feed');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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
        Route::post('/subscription/{subscription}/destroy', [SubscriptionController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('subscription.destroy');
        Route::get('/subscription/result/{subscription}', [SubscriptionController::class, 'show'])
            ->name('subscription.show');
    });
});

require __DIR__.'/auth.php';
