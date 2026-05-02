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

    @error('telegram_code')
        <div class="mt-3 text-sm font-semibold text-red-700 border-2 border-black bg-red-50 px-3 py-2">{{ $message }}</div>
    @enderror

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
                Привяжите Telegram, чтобы получать сервисные уведомления в мессенджере. Нажмите кнопку ниже — мы подготовим для вас одноразовую ссылку в бота.
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
            <div class="mt-4 space-y-4 border-2 border-black bg-white p-4 shadow-[3px_3px_0_0_rgb(15,23,42)] max-w-xl">
                <p class="text-sm font-bold uppercase tracking-wide text-slate-600">Шаги</p>
                <ol class="list-decimal list-inside space-y-2 text-sm text-slate-800 leading-relaxed font-semibold">
                    <li>Нажмите «Открыть бота в Telegram» (или скопируйте ссылку ниже).</li>
                    <li>В чате с ботом нажмите «Запустить» / Start.</li>
                    <li>Готово — привязка выполняется в момент открытия диалога с ботом.</li>
                </ol>

                <div class="flex flex-col gap-3 pt-1">
                    <a
                        href="{{ $telegramStartUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="lp-account-verify-btn inline-flex items-center justify-center text-center no-underline w-full sm:w-auto min-w-[14rem]"
                    >
                        Открыть бота в Telegram
                    </a>
                    <form method="POST" action="{{ route('cabinet.telegram.start') }}" class="inline">
                        @csrf
                        <button type="submit" class="lp-account-verify-btn lp-secondary-outline">
                            Получить новую ссылку
                        </button>
                    </form>
                </div>

                <details class="group border-t-2 border-slate-200 pt-3">
                    <summary class="cursor-pointer text-xs font-black uppercase tracking-wider text-slate-500 list-none flex items-center gap-2 [&::-webkit-details-marker]:hidden">
                        <span class="border-b-2 border-dotted border-slate-400 group-open:border-transparent">Ссылка для копирования</span>
                    </summary>
                    <p class="mt-2 break-all font-mono text-xs text-slate-700 bg-slate-50 border-2 border-black px-2 py-2 select-all">
                        {{ $telegramStartUrl }}
                    </p>
                </details>
            </div>
        @endunless
    @endif
</div>
