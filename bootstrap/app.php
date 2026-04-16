<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\SubscriptionDestroyCommand::class,
        \App\Console\Commands\SubscriptionAttachUserCommand::class,
        \App\Console\Commands\ProvisionCabinetUsersCommand::class,
        \App\Console\Commands\SubscriptionSyncPanelLimitIpCommand::class,
        \App\Console\Commands\SubscriptionClearBoundHwidCommand::class,
        \App\Console\Commands\SubscriptionUpdateQuotaCommand::class,
        \App\Console\Commands\SubscriptionCreateAdminCommand::class,
        \App\Console\Commands\TestKeysCleanupCommand::class,
        \App\Console\Commands\MailTestCommand::class,
        \App\Console\Commands\MassInviteTestMailCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'payments/wata/webhook',
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
            'admin.guest' => \App\Http\Middleware\RedirectIfAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
