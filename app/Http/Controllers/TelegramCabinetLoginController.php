<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Одноразовый вход в ЛК по подписанной ссылке для пользователей с привязанным Telegram.
 */
final class TelegramCabinetLoginController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            throw new NotFoundHttpException;
        }

        if ($user->telegram_id === null) {
            throw new NotFoundHttpException;
        }

        if (! Auth::check() || Auth::id() !== (int) $user->id) {
            Auth::login($user, remember: true);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
