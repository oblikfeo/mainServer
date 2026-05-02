<?php

namespace App\Http\Controllers;

use App\Models\TelegramLinkSession;
use App\Services\Telegram\TelegramAccountLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class TelegramLinkController extends Controller
{
    /** URL профиля с якорем на блок Telegram — после POST браузер не остаётся в «верхней» точке страницы. */
    private static function redirectToTelegramBlock(): string
    {
        return route('cabinet.profile').'#profile-telegram';
    }

    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->telegram_id !== null) {
            return Redirect::to(self::redirectToTelegramBlock())->withErrors([
                'telegram_code' => 'Telegram уже привязан.',
            ]);
        }

        if (trim((string) config('telegram.link_bot_username', '')) === '') {
            return Redirect::to(self::redirectToTelegramBlock())->withErrors([
                'telegram_code' => 'Привязка Telegram временно недоступна (не задан TELEGRAM_LINK_BOT_USERNAME на сервере).',
            ]);
        }

        [, $plain] = TelegramAccountLinkService::createSession($user);
        $request->session()->put(
            'telegram_start_url',
            TelegramAccountLinkService::buildBotDeepLink($plain)
        );

        return Redirect::to(self::redirectToTelegramBlock());
    }

    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->telegram_id === null) {
            return Redirect::to(self::redirectToTelegramBlock())->withErrors([
                'telegram_code' => 'Telegram не был привязан.',
            ]);
        }

        $user->forceFill([
            'telegram_id' => null,
            'telegram_username' => null,
            'telegram_linked_at' => null,
            'telegram_bot_blocked_at' => null,
        ])->save();

        TelegramLinkSession::query()->where('user_id', $user->id)->delete();
        $request->session()->forget('telegram_start_url');

        return Redirect::to(self::redirectToTelegramBlock())->with('status', 'telegram-unlinked');
    }
}
