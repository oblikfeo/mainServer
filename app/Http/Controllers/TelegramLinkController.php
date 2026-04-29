<?php

namespace App\Http\Controllers;

use App\Models\TelegramLinkSession;
use App\Services\Telegram\TelegramAccountLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class TelegramLinkController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Сначала подтвердите электронную почту.',
            ]);
        }

        if ($user->telegram_id !== null) {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Telegram уже привязан.',
            ]);
        }

        if (trim((string) config('telegram.link_bot_username', '')) === '') {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Привязка Telegram временно недоступна (не задан TELEGRAM_LINK_BOT_USERNAME на сервере).',
            ]);
        }

        [, $plain] = TelegramAccountLinkService::createSession($user);

        return Redirect::route('cabinet.profile')->with([
            'telegram_start_url' => TelegramAccountLinkService::buildBotDeepLink($plain),
            'status' => 'telegram-link-started',
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Сначала подтвердите электронную почту.',
            ]);
        }

        if ($user->telegram_id !== null) {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Telegram уже привязан.',
            ]);
        }

        $validated = $request->validate([
            'telegram_code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'telegram_code.required' => 'Введите код из Telegram.',
            'telegram_code.regex' => 'Код должен состоять из 6 цифр.',
        ]);

        $digits = (string) $validated['telegram_code'];

        /** @var TelegramLinkSession|null $session */
        $session = TelegramLinkSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('otp_code_hash')
            ->whereNotNull('telegram_user_id')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if ($session === null) {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Сессия не найдена или истекла. Запросите ссылку заново.',
            ]);
        }

        $expected = (string) ($session->otp_code_hash ?? '');
        $actual = TelegramAccountLinkService::hashOtpCode($digits);

        if (! hash_equals($expected, $actual)) {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Неверный код.',
            ]);
        }

        $tgId = (int) $session->telegram_user_id;
        if (TelegramAccountLinkService::telegramIdTakenByAnotherUser($tgId, $user->id)) {
            $session->delete();

            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Этот Telegram уже привязан к другому аккаунту.',
            ]);
        }

        $user->forceFill([
            'telegram_id' => $tgId,
            'telegram_username' => $session->telegram_username,
            'telegram_linked_at' => now(),
        ])->save();

        TelegramLinkSession::query()->where('user_id', $user->id)->delete();

        return Redirect::route('cabinet.profile')->with('status', 'telegram-linked');
    }

    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->telegram_id === null) {
            return Redirect::route('cabinet.profile')->withErrors([
                'telegram_code' => 'Telegram не был привязан.',
            ]);
        }

        $user->forceFill([
            'telegram_id' => null,
            'telegram_username' => null,
            'telegram_linked_at' => null,
        ])->save();

        TelegramLinkSession::query()->where('user_id', $user->id)->delete();

        return Redirect::route('cabinet.profile')->with('status', 'telegram-unlinked');
    }
}
