@extends('layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
@endphp

@section('title', $brand.' — оплата прошла успешно')
@section('meta_description', 'Платёж принят. Спасибо за оплату.')

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
            <h1>Спасибо!</h1>
            <p class="lp-agreement-sub">Платёж принят. Доступ будет активирован в ближайшее время.</p>
        </div>

        <div class="lp-footer">
            <a href="{{ url('/') }}" class="text-inherit underline underline-offset-2">На главную</a>
        </div>
    </div>
</div>
@endsection
