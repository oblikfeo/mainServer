@extends('views2::layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
@endphp

@section('title', 'Помощь с подключением — '.$brand)
@section('meta_description', 'Пошаговая инструкция: что проверить, если VPN не работает.')

@push('styles')
    @include('views2::partials.lp-f1-styles')
    @include('views2::partials.lp-header-views2-styles')
    @include('views2::partials.lp-views2-responsive-styles')
    @include('views2::partials.tutorial-styles')
    <style>[x-cloak]{display:none!important}</style>
@endpush

@section('content')
<div class="lp-f1 lp-f1-body" x-data="tutorialWizard()">
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
            {{-- Прогресс-бар (только на слайдах) --}}
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
                {{-- Интро --}}
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
                    <span class="lp-tutorial__intro-badge">Помощь</span>
                    <h1 class="lp-tutorial__intro-title">Не работает<br>соединение?</h1>
                    <p class="lp-tutorial__intro-text">
                        Пройдите 4 простых шага — чаще всего этого достаточно, чтобы всё заработало.
                    </p>
                </div>

                {{-- Слайд 1: Авиарежим --}}
                <div
                    class="lp-tutorial__slide"
                    x-show="current === 1"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">✈️</div>
                    <div class="lp-tutorial__step-label">Шаг 1 из 4</div>
                    <h2 class="lp-tutorial__title">Включите и выключите авиарежим</h2>
                    <p class="lp-tutorial__text">
                        Откройте панель быстрых настроек (свайп сверху вниз) и нажмите на иконку самолёта.
                    </p>
                    <p class="lp-tutorial__text">
                        Подождите 5 секунд и нажмите ещё раз, чтобы выключить. Это перезапустит сетевое соединение.
                    </p>
                    <div class="lp-tutorial__hint">
                        На iPhone: Настройки → Авиарежим. На Android: шторка уведомлений.
                    </div>
                </div>

                {{-- Слайд 2: Обновление подписки Happ --}}
                <div
                    class="lp-tutorial__slide"
                    x-show="current === 2"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">🔄</div>
                    <div class="lp-tutorial__step-label">Шаг 2 из 4</div>
                    <h2 class="lp-tutorial__title">Обновите подписку в Happ</h2>
                    <p class="lp-tutorial__text">
                        Откройте приложение Happ и нажмите кнопку обновления подписки (иконка со стрелками).
                    </p>
                    <p class="lp-tutorial__text">
                        Подождите, пока список серверов обновится, затем попробуйте подключиться снова.
                    </p>
                    <div class="lp-tutorial__hint">
                        Если кнопки нет — потяните список серверов вниз, чтобы обновить.
                    </div>
                </div>

                {{-- Слайд 3: Проверка интернета --}}
                <div
                    class="lp-tutorial__slide"
                    x-show="current === 3"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">📶</div>
                    <div class="lp-tutorial__step-label">Шаг 3 из 4</div>
                    <h2 class="lp-tutorial__title">Проверьте интернет</h2>
                    <p class="lp-tutorial__text">
                        Убедитесь, что у вас есть доступ в интернет без VPN. Откройте любой сайт в браузере.
                    </p>
                    <p class="lp-tutorial__text">
                        Если не работает мобильный интернет — попробуйте Wi‑Fi, и наоборот. Перезагрузите роутер, если используете Wi‑Fi.
                    </p>
                    <div class="lp-tutorial__hint">
                        Без рабочего интернета VPN подключиться не сможет.
                    </div>
                </div>

                {{-- Слайд 4: Пинг --}}
                <div
                    class="lp-tutorial__slide"
                    x-show="current === 4"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 -translate-y-3"
                >
                    <div class="lp-tutorial__icon" aria-hidden="true">📡</div>
                    <div class="lp-tutorial__step-label">Шаг 4 из 4</div>
                    <h2 class="lp-tutorial__title">Нажмите «Пинг»</h2>
                    <p class="lp-tutorial__text">
                        В приложении Happ выберите сервер и нажмите кнопку «Пинг» рядом с ним.
                    </p>
                    <p class="lp-tutorial__text">
                        Если пинг показывает число (например, 120 мс) — сервер доступен. Если «таймаут» или ошибка — попробуйте другой сервер из списка.
                    </p>
                    <button
                        type="button"
                        class="lp-tutorial__ping-btn"
                        :class="{ 'lp-tutorial__ping-btn--loading': pinging }"
                        @click="runPing()"
                        :disabled="pinging"
                    >
                        <span x-text="pinging ? 'Проверяем…' : 'Проверить соединение'"></span>
                    </button>
                    <p
                        class="lp-tutorial__ping-result"
                        x-show="pingResult"
                        x-text="pingResult"
                        :class="pingOk ? 'lp-tutorial__ping-result--ok' : 'lp-tutorial__ping-result--bad'"
                    ></p>
                </div>

                {{-- Финал --}}
                <div
                    class="lp-tutorial__done"
                    x-show="current > total"
                    x-transition:enter="transition ease-out duration-350"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <div class="lp-tutorial__done-icon" aria-hidden="true">✓</div>
                    <h2 class="lp-tutorial__title">Готово!</h2>
                    <p class="lp-tutorial__text" style="text-align:center;flex:none;">
                        Вы прошли все шаги. Если соединение всё ещё не работает — напишите в поддержку, мы поможем.
                    </p>
                </div>
            </div>

            {{-- Кнопки навигации --}}
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
function tutorialWizard() {
    return {
        current: 0,
        total: 4,
        pinging: false,
        pingResult: '',
        pingOk: false,

        next() {
            if (this.current <= this.total) {
                this.current++;
            }
        },

        prev() {
            if (this.current > this.total) {
                this.current = 0;
                this.pingResult = '';
                this.pingOk = false;
            } else if (this.current > 0) {
                this.current--;
            }
        },

        async runPing() {
            this.pinging = true;
            this.pingResult = '';
            const start = performance.now();
            try {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 5000);
                await fetch('{{ url('/') }}', { method: 'HEAD', cache: 'no-store', signal: controller.signal });
                clearTimeout(timeout);
                const ms = Math.round(performance.now() - start);
                this.pingOk = true;
                this.pingResult = 'Соединение есть (' + ms + ' мс). Попробуйте подключиться к VPN.';
            } catch (e) {
                this.pingOk = false;
                this.pingResult = 'Не удалось проверить. Проверьте интернет и попробуйте снова.';
            }
            this.pinging = false;
        }
    };
}
</script>
@endpush
