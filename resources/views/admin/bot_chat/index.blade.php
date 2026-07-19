@extends('layouts.admin')

@section('title', 'Диалоги ИИ')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">Диалоги с ИИ-ассистентом</h1>
    <p class="text-slate-500 text-sm mb-6 sm:mb-8">Обращения клиентов через кнопку «Поддержка» в Telegram-боте. Нажмите на клиента, чтобы открыть переписку.</p>

    @if ($rows->total() === 0)
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-slate-500 shadow-sm">
            Пока нет ни одного диалога.
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/5">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500">
                        <th class="px-4 py-3">Клиент</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3 text-center">Сообщений</th>
                        <th class="px-4 py-3 text-center">Оператор</th>
                        <th class="px-4 py-3">Последнее сообщение</th>
                        <th class="px-4 py-3 whitespace-nowrap">Активность</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        @php
                            $tgId = $row->telegram_user_id;
                            $username = $usernames[$tgId] ?? null;
                            $email = $emails[$tgId] ?? null;
                            $last = $lastTexts[$tgId] ?? null;
                        @endphp
                        <tr
                            class="hover:bg-slate-50 cursor-pointer transition-colors"
                            onclick="window.location='{{ route('admin.bot_chats.show', $tgId) }}'"
                        >
                            <td class="px-4 py-3 align-top">
                                <a href="{{ route('admin.bot_chats.show', $tgId) }}" class="font-semibold text-slate-900 hover:underline">
                                    {{ $username ? '@'.$username : 'ID '.$tgId }}
                                </a>
                                @if ($username)
                                    <div class="text-xs text-slate-400">ID {{ $tgId }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-slate-700">
                                {{ $email ?: '—' }}
                            </td>
                            <td class="px-4 py-3 align-top text-center text-slate-700">
                                {{ $row->messages_count }}
                            </td>
                            <td class="px-4 py-3 align-top text-center">
                                @if ($row->had_handoff)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">был</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top max-w-xs">
                                @if ($last)
                                    <span class="text-slate-400">{{ $last['role'] === 'user' ? 'Клиент:' : 'ИИ:' }}</span>
                                    <span class="text-slate-700">{{ \Illuminate\Support\Str::limit($last['content'], 90) }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top whitespace-nowrap text-slate-500">
                                {{ \Illuminate\Support\Carbon::parse($row->last_at)->format('d.m.Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $rows->links() }}
        </div>
    @endif
@endsection
