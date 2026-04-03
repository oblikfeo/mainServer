@extends('layouts.admin')

@section('title', 'Создание подписки')

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-base font-medium text-slate-600 hover:text-slate-900 mb-6">
            ← К разделам
        </a>

        <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
            Создание подписки
        </h1>
        <p class="text-lg text-slate-600 max-w-2xl">
            Раздел в разработке.
        </p>
    </div>
@endsection
