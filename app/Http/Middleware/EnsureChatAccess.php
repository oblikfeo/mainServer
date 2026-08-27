<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает на /chat пользователей с активным оплаченным периодом —
 * платной подпиской или тестовым доступом (пробная подписка / legacy test_key).
 * Без активного периода — редирект на вкладку оплаты в ЛК.
 */
class EnsureChatAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(404);
        }

        if (! $user->hasActiveNonTrialSubscription() && ! $user->hasActiveTrialAccess()) {
            return redirect()->route('cabinet.payment');
        }

        return $next($request);
    }
}
