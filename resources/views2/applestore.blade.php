@extends('views2::layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
@endphp

@section('title', 'Как сменить регион в App Store — '.$brand)
@section('meta_description', 'Пошаговая инструкция: как сменить страну Apple ID, чтобы вернуть пропавшие приложения.')

@push('styles')
    @include('views2::partials.lp-f1-styles')
    @include('views2::partials.lp-header-views2-styles')
    @include('views2::partials.lp-views2-responsive-styles')
    @include('views2::partials.tutorial-styles')
    @include('views2::partials.applestore-styles')
    <style>[x-cloak]{display:none!important}</style>
@endpush

@section('content')
<div class="lp-f1 lp-f1-body" x-data="appleStoreWizard()">
    <div class="lp-container lp-container--tutorial lp-container--applestore">
        <header class="lp-header lp-header-v2">
            <div class="lp-header__bar">
                <a href="{{ route('home') }}" class="lp-brand-line" style="text-decoration:none;color:inherit;">
                    <span class="lp-logo-heavy">{{ mb_strtoupper($brand, 'UTF-8') }}</span>
                    <span class="lp-logo-vpn">VPN</span>
                </a>
                <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer" class="lp-header-cta">Поддержка</a>
            </div>
        </header>

        <div class="lp-tutorial">
            <div class="as-stage">

                {{-- ИНТРО --}}
                <section class="as-pane" x-show="current === 0" x-cloak>
                    <div class="as-pane__inner as-pane__inner--center">
                        <span class="as-badge">iPhone и iPad</span>
                        <h1 class="as-intro-title">Пропали<br>приложения?</h1>
                        <p class="as-intro-text">
                            Многие приложения убрали из российского App&nbsp;Store. Их можно вернуть — нужно поменять страну в настройках.
                        </p>
                        <p class="as-intro-text" style="margin-top:.85rem;">
                            Шесть шагов, минут на пять. Мы проведём вас за руку.
                        </p>
                        <div class="as-note as-note--warn">
                            <strong>Сразу честно:</strong> чтобы <u>покупать</u> платные приложения, понадобится иностранная или подарочная карта. Но <u>бесплатные</u> будут скачиваться сразу.
                        </div>
                    </div>
                </section>

                {{-- ШАГ 1 --}}
                <section class="as-pane" x-show="current === 1" x-cloak>
                    <div class="as-pane__inner">
                        <div class="lp-tutorial__icon" aria-hidden="true">⚙️</div>
                        <div class="lp-tutorial__step-label">Шаг 1 из 6</div>
                        <h2 class="lp-tutorial__title">Откройте настройки</h2>
                        <p class="lp-tutorial__text">
                            Найдите на экране телефона серую иконку с шестерёнкой — это <strong>«Настройки»</strong>.
                        </p>
                        <p class="lp-tutorial__text">
                            Откройте её и нажмите на <strong>своё имя</strong> — самая первая строчка сверху, там где ваша фотография.
                        </p>
                        <div class="as-path">
                            <span class="as-path__item">Настройки</span>
                            <span class="as-path__arrow">→</span>
                            <span class="as-path__item">Ваше имя</span>
                        </div>
                    </div>
                </section>

                {{-- ШАГ 2 --}}
                <section class="as-pane" x-show="current === 2" x-cloak>
                    <div class="as-pane__inner">
                        <div class="lp-tutorial__icon" aria-hidden="true">🛒</div>
                        <div class="lp-tutorial__step-label">Шаг 2 из 6</div>
                        <h2 class="lp-tutorial__title">Контент и покупки</h2>
                        <p class="lp-tutorial__text">
                            В открывшемся списке найдите строчку <strong>«Контент и покупки»</strong> и нажмите на неё.
                        </p>
                        <p class="lp-tutorial__text">
                            Появится маленькое окошко — выберите в нём <strong>«Просмотреть»</strong>. Телефон попросит приложить палец или показать лицо.
                        </p>
                        <div class="as-path">
                            <span class="as-path__item">Контент и покупки</span>
                            <span class="as-path__arrow">→</span>
                            <span class="as-path__item">Просмотреть</span>
                        </div>
                        <div class="lp-tutorial__hint">
                            На старых телефонах строчка может называться «Медиаматериалы и покупки» — это то же самое.
                        </div>
                    </div>
                </section>

                {{-- ШАГ 3 --}}
                <section class="as-pane" x-show="current === 3" x-cloak>
                    <div class="as-pane__inner">
                        <div class="lp-tutorial__icon" aria-hidden="true">🌍</div>
                        <div class="lp-tutorial__step-label">Шаг 3 из 6</div>
                        <h2 class="lp-tutorial__title">Выберите страну</h2>
                        <p class="lp-tutorial__text">
                            Нажмите <strong>«Страна/регион»</strong>, затем <strong>«Изменить страну или регион»</strong>. Откроется длинный список стран.
                        </p>
                        <p class="lp-tutorial__text">
                            После выбора появятся условия Apple — пролистайте вниз и нажмите <strong>«Принять»</strong>.
                        </p>
                        <div class="as-choice">
                            <div class="as-choice__row">
                                <span class="as-choice__flag">🇺🇸</span>
                                <div>
                                    <div class="as-choice__name">США</div>
                                    <div class="as-choice__desc">Больше всего приложений. Берите, если сомневаетесь.</div>
                                </div>
                            </div>
                            <div class="as-choice__row">
                                <span class="as-choice__flag">🇹🇷</span>
                                <div>
                                    <div class="as-choice__name">Турция</div>
                                    <div class="as-choice__desc">Платные приложения заметно дешевле.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ШАГ 4: подготовка спрятана здесь, раскрывается по нажатию --}}
                <section class="as-pane" x-show="current === 4" x-cloak>
                    <div class="as-pane__inner">
                        <div class="lp-tutorial__icon" aria-hidden="true">💳</div>
                        <div class="lp-tutorial__step-label">Шаг 4 из 6</div>
                        <h2 class="lp-tutorial__title">Нажмите «Нет» у карты</h2>
                        <p class="lp-tutorial__text">
                            Телефон спросит про способ оплаты. В самом верху будет вариант <strong>«Нет»</strong> — выберите именно его.
                        </p>
                        <p class="lp-tutorial__text">
                            Российскую карту вписывать бесполезно — Apple её не примет. С вариантом «Нет» всё получится.
                        </p>

                        <div class="as-trouble">
                            <button
                                type="button"
                                class="as-trouble__toggle"
                                @click="trouble = !trouble"
                                :aria-expanded="trouble ? 'true' : 'false'"
                            >
                                <span>Кнопки «Нет» нет или ошибка</span>
                                <span class="as-trouble__chevron" :class="trouble ? 'as-trouble__chevron--open' : ''" aria-hidden="true">▾</span>
                            </button>

                            <div class="as-trouble__body" x-show="trouble" x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                            >
                                <p class="as-trouble__lead">
                                    Значит мешает одно из двух. Проверьте и вернитесь сюда.
                                </p>

                                <div class="as-fix">
                                    <div class="as-fix__num">1</div>
                                    <div>
                                        <div class="as-fix__title">Деньги на счёте Apple</div>
                                        <div class="as-fix__text">
                                            Даже 10&nbsp;рублей мешают смене страны. Откройте App&nbsp;Store → нажмите на своё фото в правом верхнем углу — остаток под именем. Потратьте его на любое приложение.
                                        </div>
                                    </div>
                                </div>

                                <div class="as-fix">
                                    <div class="as-fix__num">2</div>
                                    <div>
                                        <div class="as-fix__title">Подписка Apple Music</div>
                                        <div class="as-fix__text">
                                            Её нужно отменить: Настройки → ваше имя → «Подписки». Остальные подписки трогать не надо, они продолжат работать.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ШАГ 5 --}}
                <section class="as-pane" x-show="current === 5" x-cloak>
                    <div class="as-pane__inner">
                        <div class="lp-tutorial__icon" aria-hidden="true">🏠</div>
                        <div class="lp-tutorial__step-label">Шаг 5 из 6</div>
                        <h2 class="lp-tutorial__title">Впишите любой адрес</h2>
                        <p class="lp-tutorial__text">
                            Apple попросит адрес в выбранной стране. <strong>Проверять его никто не будет</strong> — письма туда не придут.
                        </p>
                        <p class="lp-tutorial__text">
                            Для США можно вписать вот это — просто перепишите:
                        </p>
                        <div class="as-fill">
                            <div class="as-fill__row"><span class="as-fill__k">Улица</span><span class="as-fill__v">1 Main St</span></div>
                            <div class="as-fill__row"><span class="as-fill__k">Город</span><span class="as-fill__v">New York</span></div>
                            <div class="as-fill__row"><span class="as-fill__k">Штат</span><span class="as-fill__v">NY</span></div>
                            <div class="as-fill__row"><span class="as-fill__k">Индекс</span><span class="as-fill__v">10001</span></div>
                        </div>
                        <div class="lp-tutorial__hint">
                            Телефон оставьте свой российский — код подтверждения на него придёт нормально.
                        </div>
                    </div>
                </section>

                {{-- ШАГ 6 --}}
                <section class="as-pane" x-show="current === 6" x-cloak>
                    <div class="as-pane__inner">
                        <div class="lp-tutorial__icon" aria-hidden="true">✅</div>
                        <div class="lp-tutorial__step-label">Шаг 6 из 6</div>
                        <h2 class="lp-tutorial__title">Откройте App Store</h2>
                        <p class="lp-tutorial__text">
                            Нажмите <strong>«Далее»</strong> в телефоне — страна поменяется. Теперь зайдите в App&nbsp;Store и найдите приложение, которого раньше не было.
                        </p>
                        <p class="lp-tutorial__text">
                            Скачалось — всё получилось.
                        </p>
                        <div class="lp-tutorial__hint">
                            Магазин выглядит по-старому? Закройте App&nbsp;Store полностью и откройте заново.
                        </div>
                    </div>
                </section>

                {{-- ФИНАЛ --}}
                <section class="as-pane" x-show="current === 7" x-cloak>
                    <div class="as-pane__inner as-pane__inner--center">
                        <div class="as-done-head">
                            <div class="lp-tutorial__done-icon" aria-hidden="true">✓</div>
                            <h2 class="lp-tutorial__title" style="margin-bottom:.5rem;">Готово!</h2>
                            <p class="as-done__text">
                                Теперь вам доступен весь каталог приложений выбранной страны.
                            </p>
                        </div>

                        <div class="as-faq">
                            <div class="as-faq__item">
                                <div class="as-faq__q">Мои приложения никуда не денутся?</div>
                                <div class="as-faq__a">Нет. Всё, что уже установлено, останется на месте и продолжит работать.</div>
                            </div>
                            <div class="as-faq__item">
                                <div class="as-faq__q">А деньги? Ничего не спишется?</div>
                                <div class="as-faq__a">Нет. Смена страны бесплатна, карту вы не привязывали.</div>
                            </div>
                            <div class="as-faq__item">
                                <div class="as-faq__q">Как потом покупать платные приложения?</div>
                                <div class="as-faq__a">Через подарочные карты той же страны — их продают в интернете. Бесплатные скачиваются без всего этого.</div>
                            </div>
                            <div class="as-faq__item">
                                <div class="as-faq__q">Можно вернуть Россию обратно?</div>
                                <div class="as-faq__a">Да, в любой момент — теми же шагами.</div>
                            </div>
                        </div>

                        <p class="as-done__text" style="margin-top:1.25rem;">
                            Что-то пошло не так? Напишите нам — поможем и подскажем.
                        </p>
                    </div>
                </section>
            </div>

            {{-- Кнопки --}}
            <div class="lp-tutorial__actions">
                <template x-if="current === 0">
                    <button type="button" class="lp-tutorial__btn lp-tutorial__btn--primary" @click="next()">
                        Начать
                    </button>
                </template>

                <template x-if="current > 0 && current <= total">
                    <button type="button" class="lp-tutorial__btn lp-tutorial__btn--primary" @click="next()">
                        <span x-text="current < total ? 'Далее' : 'Завершить'"></span>
                    </button>
                </template>

                <template x-if="current > total">
                    <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer" class="lp-tutorial__btn lp-tutorial__btn--accent">
                        Написать в Telegram
                    </a>
                </template>

                <button
                    type="button"
                    class="lp-tutorial__btn lp-tutorial__btn--secondary"
                    x-show="current > 0"
                    @click="prev()"
                >
                    <span x-text="current > total ? 'Пройти снова' : 'Назад'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function appleStoreWizard() {
    return {
        current: 0,
        total: 6,
        trouble: false,

        next() {
            if (this.current <= this.total) {
                this.current++;
                this.afterMove();
            }
        },

        prev() {
            if (this.current > this.total) {
                this.current = 0;
            } else if (this.current > 0) {
                this.current--;
            }
            this.afterMove();
        },

        afterMove() {
            // Подсказку про «нет кнопки» каждый раз сворачиваем заново.
            this.trouble = false;
            const stage = this.$el.querySelector('.as-stage');
            if (stage) stage.scrollTop = 0;
        }
    };
}
</script>
@endpush
