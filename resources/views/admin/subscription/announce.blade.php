@extends('layouts.admin')

@section('title', 'Анонс в Happ (#announce)')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-3xl w-full mx-auto space-y-6">
        <div>
            <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight">Анонс в Happ</h1>
            <p class="mt-2 text-sm sm:text-base text-slate-600 leading-relaxed">
                Текст в строке <code class="rounded bg-slate-100 px-1">#announce</code> — баннер над списком серверов в Happ.
                Поддерживает многострочный текст: переносы строк сохраняются (передаются в base64-нагрузке).
                <strong>Лимит — 200 символов</strong>, всё что длиннее обрежется.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-950 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6 text-sm text-slate-700 space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Доступные плейсхолдеры</h2>
            <ul class="text-sm space-y-1">
                <li><code class="rounded bg-white px-1 border border-slate-200">{used}</code> — сколько устройств уже привязано</li>
                <li><code class="rounded bg-white px-1 border border-slate-200">{max}</code> — лимит устройств в подписке</li>
                <li><code class="rounded bg-white px-1 border border-slate-200">{brand}</code> — название бренда (config marketing.brand_name)</li>
                <li><code class="rounded bg-white px-1 border border-slate-200">{support}</code> — URL службы поддержки</li>
            </ul>
            <p class="text-xs text-slate-500 pt-1">
                Если поле пустое — Happ покажет дефолт «{{ $announceFallback }}».
            </p>
        </div>

        <form
            method="post"
            action="{{ route('admin.subscription.announce.update') }}"
            class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4"
        >
            @csrf
            <div>
                <label for="announce_text" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    Текст анонса (хранится в базе)
                </label>
                <textarea
                    name="announce_text"
                    id="announce_text"
                    rows="6"
                    maxlength="4000"
                    placeholder="Напр.:
Устройства: {used}/{max}
Поддержка: {support}"
                    class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 font-mono text-sm focus:border-slate-400 focus:ring-slate-400"
                >{{ old('announce_text', $announceTemplate) }}</textarea>
                @error('announce_text')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-slate-500">
                    Лимит, после которого Happ обрежет: {{ $announceMaxLen }} символов.
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm space-y-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Предпросмотр (used=1, max=5)</h3>
                @if (trim($announcePreview) === '')
                    <p class="text-xs text-slate-500">Пусто.</p>
                @else
                    <pre class="whitespace-pre-wrap font-mono text-xs text-slate-800">{{ $announcePreview }}</pre>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить
                </button>
                <p class="text-xs text-slate-500 self-center">
                    Очистите поле и сохраните — вернётся дефолт.
                </p>
            </div>
        </form>

        <p class="text-xs text-slate-500">
            Документация Happ: <a href="https://www.happ.su/main/dev-docs/app-management" class="text-slate-700 underline underline-offset-2 font-medium" target="_blank" rel="noopener noreferrer">app-management</a>.
            Поле уходит как <code class="rounded bg-slate-100 px-1">announce: base64:&lt;…&gt;</code> и в HTTP-заголовке, и в теле подписки.
        </p>
    </div>
@endsection
