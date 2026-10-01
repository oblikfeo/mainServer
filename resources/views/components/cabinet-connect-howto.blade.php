@props(['open' => false])

@php
    $androidAppUrl = config('marketing.apps.android_url', 'https://play.google.com/store/apps/details?id=com.happproxy');
    $desktopAppUrl = config('marketing.apps.desktop_url', 'https://www.happ.su/main/ru');
    $newsTgUrl = 'https://t.me/Nadezhda_VPN';
@endphp

<div {{ $attributes->class(['lp-howto', 'lp-hw']) }} x-data="{ hw: @js((bool) $open) }">
    <button type="button" class="lp-hw__toggle" x-on:click="hw = !hw" :aria-expanded="hw">
        <span x-show="!hw" @if ($open) x-cloak @endif>Подключиться</span>
        <span x-show="hw" @unless ($open) x-cloak @endunless>Скрыть инструкцию</span>
    </button>

    <ol class="lp-hw__steps" x-show="hw" @unless ($open) x-cloak @endunless x-transition>
        <li class="lp-hw__step">
            <div class="lp-hw__head"><span class="lp-hw__num">1</span>Ставим приложение</div>

            <div class="lp-hw__ios">
                <img class="lp-hw__ios-icon" src="{{ asset('apps/happ.jpg') }}" alt="" width="40" height="40">
                <div class="lp-hw__ios-text">
                    <b>iPhone:</b> найдите в App Store <b>Happ</b>
                </div>
                <a class="lp-hw__ios-help" href="{{ route('applestore') }}">Не находится?</a>
            </div>

            <div class="lp-hw__apps">
                <a class="lp-hw__app" href="{{ $androidAppUrl }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ asset('apps/happ.jpg') }}" alt="" width="28" height="28">
                    <span>Android</span>
                </a>
                <a class="lp-hw__app" href="{{ $desktopAppUrl }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ asset('apps/happ.jpg') }}" alt="" width="28" height="28">
                    <span>Компьютер</span>
                </a>
            </div>
        </li>

        <li class="lp-hw__step">
            <div class="lp-hw__head"><span class="lp-hw__num">2</span>Копируем ссылку</div>
            {{ $slot }}
        </li>

        <li class="lp-hw__step">
            <div class="lp-hw__head"><span class="lp-hw__num">3</span>Вставляем в приложение</div>
            <div class="lp-hw__text">
                Откройте Happ и нажмите <b>«Вставить из буфера обмена»</b> (или «Import from clipboard»).
            </div>
        </li>

        <li class="lp-hw__step lp-hw__step--tg">
            <div class="lp-hw__head"><span class="lp-hw__num">4</span>Подпишитесь на новости</div>
            <a class="lp-hw__tg" href="{{ $newsTgUrl }}" target="_blank" rel="noopener noreferrer">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M21.94 4.3 18.7 19.6c-.24 1.07-.88 1.34-1.78.83l-4.93-3.63-2.38 2.29c-.26.26-.48.48-.99.48l.35-5.01 9.13-8.25c.4-.35-.09-.55-.61-.2L6.2 13.2l-4.86-1.52c-1.06-.33-1.08-1.06.22-1.57L20.55 2.8c.88-.33 1.65.2 1.39 1.5z"/></svg>
                <span>Канал Надежды в Telegram</span>
            </a>
            <div class="lp-hw__note">Там первыми пишем о сбоях и обновлениях.</div>
        </li>
    </ol>
</div>
