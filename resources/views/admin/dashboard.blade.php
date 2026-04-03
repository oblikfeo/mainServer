@extends('layouts.admin')

@section('title', 'Панель')

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Ключи и серверы</h2>
            <p class="text-gray-600 text-sm mb-6">
                Здесь будет логика выпуска ключей, связок и статуса панелей. Сейчас — заглушка после авторизации через <code class="bg-gray-100 px-1 rounded">ADMIN_USERNAME</code> / <code class="bg-gray-100 px-1 rounded">ADMIN_PASSWORD</code> в <code class="bg-gray-100 px-1 rounded">.env</code>.
            </p>
            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                <li>Маршрут: <code class="bg-gray-100 px-1 rounded">/admin</code></li>
                <li>Вход: <code class="bg-gray-100 px-1 rounded">/admin/login</code></li>
            </ul>
        </div>
    </div>
@endsection
