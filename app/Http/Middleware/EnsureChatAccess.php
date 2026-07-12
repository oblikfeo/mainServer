<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Пускает на /chat только пользователей с флагом users.chat_access. */
class EnsureChatAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! (bool) $user->chat_access) {
            abort(404);
        }

        return $next($request);
    }
}
