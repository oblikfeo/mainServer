<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('admin.username') === '' || config('admin.password') === '') {
            abort(503, 'Admin credentials are not set in environment.');
        }

        if ($request->session()->get('admin_auth') !== true) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
