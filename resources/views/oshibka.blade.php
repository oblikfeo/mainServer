@extends('layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
@endphp

@section('title', $brand.' — ошибка оплаты')
@section('meta_description', 'Платёж не прошёл. Попробуйте снова или обратитесь в поддержку.')

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
            <h1>Не удалось оплатить</h1>
            <p class="lp-agreement-sub">Попробуйте оформить платёж ещё раз. Если списание прошло с карты, но страница показала ошибку, напишите в поддержку — разберёмся.</p>
        </div>

        <div class="lp-footer">
            @include('partials.lp-footer-support')
            <a href="{{ url('/') }}" class="text-inherit underline underline-offset-2">На главную</a>
        </div>
    </div>
</div>
@endsection
