@php
    $telegramStartUrl = session('telegram_start_url');
@endphp

<div class="lp-profile-block" id="profile-telegram">
    <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Telegram</h2>

    @if (session('status') === 'telegram-linked')
        <p class="mt-3 text-sm font-semibold text-emerald-900 border-2 border-black bg-emerald-50 px-3 py-2">
            Telegram привязан к аккаунту.
        </p>
    @endif
    @if (session('status') === 'telegram-unlinked')
        <p class="mt-3 text-sm font-semibold text-slate-900 border-2 border-black bg-slate-50 px-3 py-2">
            Привязка Telegram отключена.
        </p>
    @endif

    @if ($user->telegram_id)
        <dl class="lp-dl-grid lp-dl-grid--account mt-3">
            <div>
                <dt>Статус</dt>
                <dd>
                    <span class="lp-badge-pill lp-badge-pill--ok">Привязан</span>
                </dd>
            </div>
            <div>
                <dt>Telegram</dt>
                <dd class="font-mono break-all">
                    @if ($user->telegram_username)
                        {{ '@'.$user->telegram_username }}
                    @else
                        id {{ $user->telegram_id }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="sr-only">Отвязать</dt>
                <dd class="lp-dl-grid__action">
                    <form method="POST" action="{{ route('cabinet.telegram.unlink') }}" class="inline">
                        @csrf
                        <button type="submit" class="lp-account-verify-btn lp-secondary-outline">
                            Отвязать Telegram
                        </button>
                    </form>
                </dd>
            </div>
        </dl>
    @else
        @unless ($telegramStartUrl)
            <p class="mt-3 text-sm text-slate-700 leading-relaxed">
                Нажмите кнопку — на этой странице ниже появится ссылка для открытия бота. После «Запустить» в Telegram привязка завершится автоматически.
            </p>
            <div class="mt-4 flex flex-wrap gap-3 items-center">
                <form method="POST" action="{{ route('cabinet.telegram.start') }}">
                    @csrf
                    <button type="submit" class="lp-account-verify-btn">
                        Получить ссылку на бота
                    </button>
                </form>
            </div>
        @else
            <p class="mt-3 break-all font-mono text-sm border-2 border-black bg-white px-2 py-2">
                <a href="{{ $telegramStartUrl }}" target="_blank" rel="noopener noreferrer" class="underline font-semibold text-slate-900">{{ $telegramStartUrl }}</a>
            </p>
        @endunless
    @endif
</div>
