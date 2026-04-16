<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Сохраняет ?ref=код в сессии для привязки при регистрации (гость).
 */
final class CaptureReferralCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            return $next($request);
        }

        $raw = $request->query('ref');
        if (! is_string($raw) || $raw === '') {
            return $next($request);
        }

        $code = strtolower((string) preg_replace('/[^a-z0-9]/', '', substr($raw, 0, 16)));
        if ($code === '') {
            return $next($request);
        }

        if (User::query()->where('referral_code', $code)->exists()) {
            $request->session()->put('pending_referral_code', $code);
        }

        return $next($request);
    }
}
