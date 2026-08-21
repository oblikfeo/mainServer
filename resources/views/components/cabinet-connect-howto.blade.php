@php
    $androidAppUrl = config('marketing.apps.android_url', 'https://play.google.com/store/apps/details?id=com.happproxy');
    $desktopAppUrl = config('marketing.apps.desktop_url', 'https://www.happ.su/main/ru');
    $happIosUrl = config('marketing.apps.happ_ios_url', 'https://apps.apple.com/us/app/happ-proxy-utility/id6504287215');
    $incyIosUrl = config('marketing.apps.incy_ios_url', 'https://apps.apple.com/app/incy/id6756943388');
@endphp

<div {{ $attributes->class(['lp-howto']) }}>
    <div class="lp-field-label">Как подключиться</div>
    <div class="lp-steps">
        <div class="lp-step">
            <div class="lp-step__num">1</div>
            <div class="lp-step__content">
                <div class="lp-step__title">Ставим приложение</div>

                <div class="lp-connect-ios">
                    <div class="lp-connect-ios__label">iPhone / iPad</div>
                    <div class="lp-app-chips" role="list" aria-label="Приложения для iOS">
                        <a class="lp-app-chip lp-app-chip--link" role="listitem" href="{{ $happIosUrl }}" target="_blank" rel="noopener noreferrer">
                            <img class="lp-app-chip__icon" src="{{ asset('apps/happ.jpg') }}" alt="Иконка Happ" width="56" height="56">
                            <span class="lp-app-chip__meta">
                                <span class="lp-app-chip__name">Happ</span>
                                <span class="lp-app-chip__hint">App Store</span>
                            </span>
                        </a>
                        <a class="lp-app-chip lp-app-chip--link" role="listitem" href="{{ $incyIosUrl }}" target="_blank" rel="noopener noreferrer">
                            <img class="lp-app-chip__icon" src="{{ asset('apps/incy.jpg') }}" alt="Иконка Incy" width="56" height="56">
                            <span class="lp-app-chip__meta">
                                <span class="lp-app-chip__name">Incy</span>
                                <span class="lp-app-chip__hint">App Store</span>
                            </span>
                        </a>
                    </div>
                </div>

                <div class="lp-connect-dl">
                    <div class="lp-connect-ios__label">Android и ПК</div>
                    <div class="lp-store-grid lp-store-grid--two" role="list" aria-label="Скачать Happ">
                        <a class="lp-store-btn" role="listitem" href="{{ $androidAppUrl }}" target="_blank" rel="noopener noreferrer">
                            <img class="lp-store-btn__appicon" src="{{ asset('apps/happ.jpg') }}" alt="" width="40" height="40">
                            <span class="lp-store-btn__text">
                                <span class="lp-store-btn__kicker">Google Play</span>
                                <span class="lp-store-btn__title">Android</span>
                            </span>
                        </a>
                        <a class="lp-store-btn" role="listitem" href="{{ $desktopAppUrl }}" target="_blank" rel="noopener noreferrer">
                            <img class="lp-store-btn__appicon" src="{{ asset('apps/happ.jpg') }}" alt="" width="40" height="40">
                            <span class="lp-store-btn__text">
                                <span class="lp-store-btn__kicker">happ.su</span>
                                <span class="lp-store-btn__title">Windows / ПК</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="lp-step">
            <div class="lp-step__num">2</div>
            <div class="lp-step__content">
                <div class="lp-step__title">Копируем ссылку</div>
                {{ $slot }}
            </div>
        </div>

        <div class="lp-step">
            <div class="lp-step__num">3</div>
            <div class="lp-step__content">
                <div class="lp-step__title">Вставляем в приложение</div>
                <div class="lp-step__text">Откройте Happ или Incy и нажмите «Вставить из буфера обмена» (или «Import from clipboard»).</div>
            </div>
        </div>
    </div>
</div>
