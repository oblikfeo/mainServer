@extends('layouts.admin')

@section('title', 'Диалог клиента')

@section('content')
    <a
        href="{{ route('admin.bot_chats') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← К списку
    </a>

    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
            {{ $username ? '@'.$username : 'ID '.$telegramUserId }}
        </h1>
        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
            <span>Telegram ID: {{ $telegramUserId }}</span>
            <span>Email: {{ $email ?: 'аккаунт не привязан' }}</span>
            <span>Сообщений: {{ $messages->count() }}</span>
        </div>
    </div>

    <div class="space-y-4">
        @php $lastDay = null; @endphp
        @foreach ($messages as $msg)
            @php $day = $msg->created_at?->format('d.m.Y'); @endphp
            @if ($day !== $lastDay)
                <div class="flex justify-center">
                    <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">{{ $day }}</span>
                </div>
                @php $lastDay = $day; @endphp
            @endif

            @if ($msg->role === 'user')
                <div class="flex justify-end">
                    <div class="max-w-[85%] sm:max-w-[70%] rounded-2xl rounded-br-sm bg-slate-900 px-4 py-2.5 text-white shadow-sm">
                        <div class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ $msg->content }}</div>
                        <div class="mt-1 text-[11px] text-slate-400 text-right">{{ $msg->created_at?->format('H:i') }}</div>
                    </div>
                </div>
            @else
                <div class="flex justify-start">
                    <div class="max-w-[85%] sm:max-w-[70%] rounded-2xl rounded-bl-sm border border-slate-200 bg-white px-4 py-2.5 text-slate-900 shadow-sm">
                        <div class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">ИИ-ассистент</div>
                        <div class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ $msg->content }}</div>
                        <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                            <span>{{ $msg->created_at?->format('H:i') }}</span>
                            @if ($msg->handoff)
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">→ передан оператору</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endsection
