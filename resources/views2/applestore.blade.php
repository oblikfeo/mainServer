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
    <div class="lp-container lp-container--tutorial">
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
            {{-- Прогресс: только на шагах --}}
            <div class="lp-tutorial__progress" x-show="current > 0 && current <= total" x-cloak>
                <template x-for="i in total" :key="i">
                    <div
                        class="lp-tutorial__dot"
                        :class="{
                            'lp-tutorial__dot--active': i === current,
                            'lp-tutorial__dot--done': i < current
                        }"
                    ></div>
                </template>
            </div>

            <div class="lp-tutorial__stage">

                {{-- ИНТРО --}}
                <div
                    class="lp-tutorial__intro"
                    x-show="current === 0"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <span class="lp-tutorial__intro-badge">iPhone и iPad</span>
                    <h1 class="lp-tutorial__intro-title">Пропали<br>приложения?</h1>
                    <p class="lp-tutorial__intro-text">
                        Многие приложения убрали из российского App&nbsp;Store. Их можно вернуть — нужно поменять страну в настройках.
                    </p>
                    <p class="lp-tutorial__intro-text" style="margin-top:0.85rem;">
                        Это 8 шагов, минут на десять. Мы проведём вас за руку.
                    </p>

                    <div class="as-note as-note--warn">
                        <strong>Сразу честно:</strong> чтобы <u>покупать</u> платные приложения, понадобится иностранная карта или подарочная карта. Но <u>бесплатные</u> приложения будут скачиваться сразу.
                    </div>
                </div>

                {{-- ШАГ 1 --}}
                <div class="lp-tutorial__slide" x-show="current === 1"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">💰</div>
                    <div class="lp-tutorial__step-label">Шаг 1 из 8 · Подготовка</div>
                    <h2 class="lp-tutorial__title">Потратьте деньги со счёта</h2>
                    <p class="lp-tutorial__text">
                        Если на вашем счёте Apple остались деньги — хоть 10 рублей — сменить страну не получится. Apple не разрешит.
                    </p>
                    <p class="lp-tutorial__text">
                        <strong>Где посмотреть:</strong> откройте App&nbsp;Store → нажмите на свою фотографию в правом верхнем углу. Остаток будет под вашим именем.
                    </p>
                    <div class="lp-tutorial__hint">
                        Денег нет или написано 0&nbsp;₽ — отлично, просто жмите «Далее». Если деньги есть — купите на них что угодно, чтобы счёт обнулился.
                    </div>
                </div>

                {{-- ШАГ 2 --}}
                <div class="lp-tutorial__slide" x-show="current === 2"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">🎵</div>
                    <div class="lp-tutorial__step-label">Шаг 2 из 8 · Подготовка</div>
                    <h2 class="lp-tutorial__title">Отключите Apple Music</h2>
                    <p class="lp-tutorial__text">
                        Если вы платите за <strong>Apple&nbsp;Music</strong> — эту подписку придётся отменить. Иначе страна не поменяется.
                    </p>
                    <p class="lp-tutorial__text">
                        <strong>Где отменить:</strong> Настройки → ваше имя сверху → «Подписки».
                    </p>
                    <div class="lp-tutorial__hint">
                        Остальные подписки отменять <u>не нужно</u> — они продолжат работать. Не пользуетесь Apple&nbsp;Music — пропускайте шаг.
                    </div>
                </div>

                {{-- ШАГ 3 --}}
                <div class="lp-tutorial__slide" x-show="current === 3"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">⚙️</div>
                    <div class="lp-tutorial__step-label">Шаг 3 из 8 · Начинаем</div>
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

                {{-- ШАГ 4 --}}
                <div class="lp-tutorial__slide" x-show="current === 4"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">🛒</div>
                    <div class="lp-tutorial__step-label">Шаг 4 из 8 · Начинаем</div>
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

                {{-- ШАГ 5 --}}
                <div class="lp-tutorial__slide" x-show="current === 5"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">🌍</div>
                    <div class="lp-tutorial__step-label">Шаг 5 из 8 · Главное</div>
                    <h2 class="lp-tutorial__title">Выберите страну</h2>
                    <p class="lp-tutorial__text">
                        Нажмите <strong>«Страна/регион»</strong>, затем <strong>«Изменить страну или регион»</strong>. Откроется длинный список стран.
                    </p>
                    <p class="lp-tutorial__text">
                        Какую выбрать — смотрите ниже. После выбора появятся условия Apple, пролистайте вниз и нажмите <strong>«Принять»</strong>.
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

                {{-- ШАГ 6 --}}
                <div class="lp-tutorial__slide" x-show="current === 6"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">💳</div>
                    <div class="lp-tutorial__step-label">Шаг 6 из 8 · Главное</div>
                    <h2 class="lp-tutorial__title">Нажмите «Нет» у карты</h2>
                    <p class="lp-tutorial__text">
                        Телефон спросит про способ оплаты. В самом верху будет вариант <strong>«Нет»</strong> — выберите именно его.
                    </p>
                    <p class="lp-tutorial__text">
                        Российскую карту вписывать бесполезно — Apple её не примет. С вариантом «Нет» всё получится.
                    </p>
                    <div class="as-note as-note--warn">
                        <strong>Нет кнопки «Нет»?</strong><br>
                        Значит осталась активная подписка или деньги на счету. Вернитесь к шагам 1 и 2.
                    </div>
                </div>

                {{-- ШАГ 7 --}}
                <div class="lp-tutorial__slide" x-show="current === 7"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">🏠</div>
                    <div class="lp-tutorial__step-label">Шаг 7 из 8 · Почти всё</div>
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

                {{-- ШАГ 8 --}}
                <div class="lp-tutorial__slide" x-show="current === 8"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">✅</div>
                    <div class="lp-tutorial__step-label">Шаг 8 из 8 · Проверка</div>
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

                {{-- ФИНАЛ --}}
                <div class="lp-tutorial__done as-done" x-show="current > total"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <div class="lp-tutorial__done-icon" aria-hidden="true">✓</div>
                    <h2 class="lp-tutorial__title">Готово!</h2>
                    <p class="lp-tutorial__text as-done__text">
                        Теперь вам доступен весь каталог приложений выбранной страны.
                    </p>

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

                    <p class="lp-tutorial__text as-done__text" style="margin-top:1.25rem;">
                        Что-то пошло не так? Напишите нам — поможем и подскажем.
                    </p>
                </div>
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
        total: 8,

        next() {
            if (this.current <= this.total) {
                this.current++;
                this.toTop();
            }
        },

        prev() {
            if (this.current > this.total) {
                this.current = 0;
            } else if (this.current > 0) {
                this.current--;
            }
            this.toTop();
        },

        // Длинные шаги прокручиваются — при переходе возвращаем взгляд наверх.
        toTop() {
            const stage = this.$el.querySelector('.lp-tutorial__stage');
            if (stage) stage.scrollTop = 0;
        }
    };
}
</script>
@endpush
