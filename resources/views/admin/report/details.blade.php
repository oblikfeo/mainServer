<div class="text-sm text-slate-700 space-y-4 max-w-4xl">
    <p class="flex flex-wrap gap-x-2 gap-y-1 items-baseline">
        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500">Устройства (лимит уник. IP FI+NL)</span>
        <span class="font-semibold text-slate-900">{{ $subscription->devices }}</span>
    </p>
    @php
        $crow = isset($connectionBySubId) ? ($connectionBySubId[$subscription->id] ?? null) : null;
    @endphp
    @if ($crow)
        <p class="text-xs text-slate-600 rounded-xl border border-slate-200 bg-white px-3 py-2">
            Сейчас уникальных IP (панель): <span class="font-bold text-slate-900">{{ $crow['online_ip_count'] }}</span>
            @if ($crow['limit'] > 0)
                · лимит {{ $crow['limit'] }}
                @if ($crow['over'])
                    <span class="text-rose-600 font-bold"> — превышение</span>
                @endif
            @endif
        </p>
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach (config('xui.bundle_order', ['fi', 'nl']) as $bundleKey)
            @php
                $col = $bundleKey.'_sub_id';
                $sid = $subscription->{$col} ?? '';
                $t = is_string($sid) && $sid !== '' ? (($trafficMaps[$bundleKey] ?? [])[$sid] ?? null) : null;
                $node = config('xui.nodes.'.$bundleKey, []);
                $label = is_array($node) ? (string) ($node['vless_display_name'] ?? strtoupper($bundleKey)) : strtoupper($bundleKey);
            @endphp
            @if (is_string($sid) && $sid !== '')
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-900/5 min-w-0">
                    <div class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500 mb-2 break-words">{{ $label }}</div>
                    <p class="font-mono text-xs break-all text-slate-500 mb-2 leading-relaxed">subId: {{ $sid }}</p>
                    <p class="text-slate-900 font-medium tabular-nums text-xs sm:text-sm">
                        @if ($t)
                            ↑ {{ $byteFmt($t['up']) }} · ↓ {{ $byteFmt($t['down']) }} · Σ {{ $byteFmt($t['up'] + $t['down']) }}
                        @else
                            —
                        @endif
                    </p>
                </div>
            @endif
        @endforeach
    </div>
    <p class="text-xs text-slate-500 break-all pt-1 border-t border-slate-200/60 font-mono leading-relaxed">
        {{ rtrim(config('app.url'), '/') }}/sub/{{ $subscription->token }}
    </p>

    <div class="pt-4 border-t border-slate-200/60">
        @include('admin.subscription._assign_owner', ['subscription' => $subscription])
    </div>
</div>
