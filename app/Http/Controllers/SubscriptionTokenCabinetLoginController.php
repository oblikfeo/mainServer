<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\TestKey;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Вход в личный кабинет по токену подписки. Ссылка из Happ.
 * Query intent=renew — после входа открыть вкладку продления.
 */
final class SubscriptionTokenCabinetLoginController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        if (! (bool) config('marketing.happ_cabinet_link_enabled', true)) {
            throw new NotFoundHttpException;
        }

        if (strlen($token) > 120) {
            throw new NotFoundHttpException;
        }

        $subscription = Subscription::query()->where('token', $token)->first();
        if ($subscription !== null) {
            return $this->loginForUser($request, $subscription->user_id, 'Подписка не привязана к аккаунту. Войдите с тем email, что указывали при покупке.');
        }

        $testKey = TestKey::query()
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($testKey !== null) {
            return $this->loginForUser($request, $testKey->user_id, 'Тестовый ключ не привязан к аккаунту.');
        }

        throw new NotFoundHttpException;
    }

    private function loginForUser(Request $request, ?int $userId, string $unlinkMessage): RedirectResponse
    {
        if ($userId === null || $userId < 1) {
            return redirect()
                ->route('login')
                ->with('status', $unlinkMessage);
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            throw new NotFoundHttpException;
        }

        if (! Auth::check() || Auth::id() !== (int) $user->id) {
            Auth::login($user, remember: true);
        }

        $request->session()->regenerate();

        if ($request->query('intent') === 'renew') {
            return redirect()->to(route('cabinet.renewal', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
