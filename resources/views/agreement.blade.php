@extends('layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $publishedConfig = config('marketing.offer_published_at');
    $offerDate = filled($publishedConfig)
        ? $publishedConfig
        : now()->timezone(config('app.timezone'))->format('d.m.Y');
    $fio = filled(config('marketing.offer_executor_name')) ? config('marketing.offer_executor_name') : '—';
    $inn = filled(config('marketing.offer_executor_inn')) ? config('marketing.offer_executor_inn') : '—';
    $execEmail = filled(config('marketing.offer_executor_email'))
        ? config('marketing.offer_executor_email')
        : (filled(config('marketing.support_email')) ? config('marketing.support_email') : '—');
@endphp

@section('title', $brand.' — публичная оферта')
@section('meta_description', 'Публичная оферта на оказание услуг доступа к онлайн-сервису.')

@push('styles')
    @include('partials.lp-f1-styles')
@endpush

@section('content')
<div class="lp-f1 lp-f1-body">
    <div class="lp-container lp-container--agreement">
        <div class="lp-header">
            <a href="{{ url('/') }}" class="lp-logo lp-cabinet-header__brand">{{ $brand }}</a>
            @auth
                <a href="{{ route('dashboard') }}" class="lp-login-btn">Кабинет</a>
            @else
                <a href="{{ route('login') }}" class="lp-login-btn">Кабинет</a>
            @endauth
        </div>

        <div class="lp-agreement-hero">
            <h1>Публичная оферта</h1>
            <p class="lp-agreement-sub">на оказание услуг доступа к онлайн-сервису</p>
            <p class="lp-agreement-meta">Дата публикации: {{ $offerDate }}</p>
        </div>

        <section class="lp-agreement-section" aria-labelledby="agreement-s1">
            <h2 id="agreement-s1">1. Общие положения</h2>
            <ol>
                <li>Настоящий документ является официальным предложением (публичной офертой) физического лица, применяющего специальный налоговый режим «Налог на профессиональный доход» (далее — Исполнитель), заключить договор на оказание услуг на изложенных ниже условиях.</li>
                <li>Акцептом настоящей оферты является оплата услуг Пользователем.</li>
                <li>С момента акцепта Пользователь считается ознакомленным и согласным с условиями настоящей оферты.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s2">
            <h2 id="agreement-s2">2. Предмет договора</h2>
            <ol>
                <li>Исполнитель предоставляет Пользователю доступ к онлайн-сервису, включающему программное обеспечение и удалённую серверную инфраструктуру, предназначенные для обеспечения стабильной работы интернет-соединения.</li>
                <li>Услуга предоставляется в формате удалённого доступа через личный кабинет Пользователя.</li>
                <li>Услуга не является телекоммуникационной услугой и не гарантирует доступ к конкретным интернет-ресурсам.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s3">
            <h2 id="agreement-s3">3. Порядок предоставления услуг</h2>
            <ol>
                <li>После оплаты Пользователю предоставляется доступ к сервису.</li>
                <li>Доступ активируется автоматически либо в разумный срок после подтверждения оплаты.</li>
                <li>Срок действия доступа определяется выбранным тарифом.</li>
                <li>Услуга считается оказанной с момента предоставления доступа к сервису.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s4">
            <h2 id="agreement-s4">4. Стоимость и порядок оплаты</h2>
            <ol>
                <li>Стоимость услуг указывается на сайте Исполнителя.</li>
                <li>Оплата производится через доступные платёжные методы.</li>
                <li>Услуга предоставляется по модели подписки.</li>
                <li>Пользователь самостоятельно контролирует продление и оплату доступа.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s5">
            <h2 id="agreement-s5">5. Права и обязанности сторон</h2>
            <h3>5.1. Исполнитель обязуется:</h3>
            <ul>
                <li>обеспечить доступ к сервису</li>
                <li>поддерживать работоспособность сервиса, за исключением технических сбоев</li>
            </ul>
            <h3>5.2. Исполнитель вправе:</h3>
            <ul>
                <li>приостанавливать доступ при нарушении условий</li>
                <li>проводить технические работы</li>
            </ul>
            <h3>5.3. Пользователь обязуется:</h3>
            <ul>
                <li>использовать сервис в рамках законодательства</li>
                <li>не нарушать работу сервиса</li>
            </ul>
            <h3>5.4. Пользователь вправе:</h3>
            <ul>
                <li>получать доступ к сервису в оплаченный период</li>
            </ul>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s6">
            <h2 id="agreement-s6">6. Ограничение ответственности</h2>
            <ol>
                <li>Исполнитель не несёт ответственности за:
                    <ul>
                        <li>невозможность использования сервиса по причинам, не зависящим от него</li>
                        <li>действия третьих лиц и операторов связи</li>
                        <li>ограничения доступа к отдельным ресурсам</li>
                    </ul>
                </li>
                <li>Исполнитель не гарантирует бесперебойную работу сервиса в 100% времени.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s7">
            <h2 id="agreement-s7">7. Возврат средств</h2>
            <ol>
                <li>Возврат средств возможен в случае подтверждённой технической невозможности использования сервиса.</li>
                <li>Решение о возврате принимается Исполнителем на основании обращения Пользователя.</li>
                <li>Возврат осуществляется тем же способом, которым была произведена оплата.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s8">
            <h2 id="agreement-s8">8. Персональные данные</h2>
            <ol>
                <li>Пользователь даёт согласие на обработку персональных данных (email и технические данные).</li>
                <li>Данные используются исключительно для предоставления услуги.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s9">
            <h2 id="agreement-s9">9. Заключительные положения</h2>
            <ol>
                <li>Исполнитель вправе изменять условия оферты без предварительного уведомления.</li>
                <li>Актуальная версия всегда размещается на сайте.</li>
                <li>Пользователь самостоятельно определяет цели использования сервиса и несёт ответственность за соблюдение законодательства.</li>
            </ol>
        </section>

        <section class="lp-agreement-section" aria-labelledby="agreement-s10">
            <h2 id="agreement-s10">10. Реквизиты Исполнителя</h2>
            <div class="lp-agreement-requisites">
                <span>ФИО: {{ $fio }}</span>
                <span>ИНН: {{ $inn }}</span>
                <span>Статус: самозанятый (НПД)</span>
                <span>Email:
                    @if ($execEmail !== '—')
                        <a href="mailto:{{ $execEmail }}" class="underline font-black text-inherit">{{ $execEmail }}</a>
                    @else
                        —
                    @endif
                </span>
            </div>
        </section>

        <div class="lp-footer">
            Документ носит информационный характер. Акцепт оферты — оплата услуг.<br><br>
            <a href="{{ url('/') }}" class="text-inherit underline underline-offset-2">На главную</a>
            @if ($execEmail !== '—')
                · <a href="mailto:{{ $execEmail }}" class="text-inherit underline underline-offset-2">Связь с Исполнителем</a>
            @endif
        </div>
    </div>
</div>
@endsection
