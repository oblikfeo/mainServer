<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Раздел «Маркетинг»: пускает основного админа и маркетинговый вход. */
class EnsureMarketingAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        if ($session->get('admin_auth') !== true && $session->get('marketing_auth') !== true) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
