{{-- Тарифы на главной одной таблицей: сроки из payments.account.terms + строка акции. --}}
@php
    $terms = \App\Services\Account\AccountPricing::terms();
    $promo = \App\Services\Account\AccountPricing::promo();
    $base = \App\Services\Account\AccountPricing::baseDevices();
    $deviceRub = \App\Services\Account\AccountPricing::deviceMonthRub();
    $fmt = static fn (int $n): string => number_format($n, 0, ',', ' ');
    $plural = static fn (int $n, string $a, string $b, string $c): string => ($n % 10 === 1 && $n % 100 !== 11) ? $a : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? $b : $c);
@endphp
<style>
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable-sub { text-align: center; font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif; font-size: 16px; font-weight: 700; color: #fff; margin: -10px 0 30px; }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable { width: 100%; border-collapse: collapse; background: #fff; border: var(--mock-border); box-shadow: var(--mock-shadow); font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif; margin-bottom: 30px; }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable th { background: var(--mock-dark); color: #fff; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; text-align: left; padding: 12px 16px; }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable td { padding: 14px 16px; border-top: 2px solid var(--mock-dark); font-size: 16px; font-weight: 800; color: var(--mock-dark); vertical-align: middle; }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable td.lp-ptable__price { font-family: "Syne", ui-sans-serif, system-ui, sans-serif; font-size: 28px; white-space: nowrap; }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable td.lp-ptable__month { color: #666; font-size: 14px; white-space: nowrap; }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable tr.lp-ptable__promo td { background: var(--mock-accent); }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable tr.lp-ptable__promo td.lp-ptable__month { color: var(--mock-dark); }
    .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable__tag { display: inline-block; background: var(--mock-dark); color: #fff; font-size: 11px; padding: 3px 8px; text-transform: uppercase; margin-right: 8px; vertical-align: middle; }
    @media (max-width: 767px) {
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable th,
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable td { padding: 10px; }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable td.lp-ptable__price { font-size: 22px; }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable__wide { display: none; }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-ptable__tag { display: block; width: max-content; margin: 0 0 4px; }
    }
</style>
<p class="lp-ptable-sub">Во всех тарифах {{ $base }} {{ $plural($base, 'устройство', 'устройства', 'устройств') }}. Каждое следующее — +{{ $fmt($deviceRub) }}&nbsp;₽ в месяц.</p>
<table class="lp-ptable">
    <tr>
        <th>Срок</th>
        <th>Устройств</th>
        <th>Цена</th>
        <th class="lp-ptable__wide">В месяц</th>
    </tr>
    @foreach ($terms as $t)
        <tr>
            <td>{{ $t['label'] }}</td>
            <td>{{ $base }}</td>
            <td class="lp-ptable__price">{{ $fmt($t['amount_rub']) }}&nbsp;₽</td>
            <td class="lp-ptable__month lp-ptable__wide">{{ $fmt($t['per_month_rub']) }}&nbsp;₽@if ($t['discount_percent'] > 0) · −{{ $t['discount_percent'] }}%@endif</td>
        </tr>
    @endforeach
    @if ($promo)
        <tr class="lp-ptable__promo">
            <td><span class="lp-ptable__tag">Акция</span>{{ $promo['months'] }} {{ $plural($promo['months'], 'месяц', 'месяца', 'месяцев') }}</td>
            <td>{{ $promo['devices'] }}</td>
            <td class="lp-ptable__price">{{ $fmt($promo['amount_rub']) }}&nbsp;₽</td>
            <td class="lp-ptable__month lp-ptable__wide">@if ($promo['saving_rub'] > 0)выгода {{ $fmt($promo['saving_rub']) }}&nbsp;₽@endif</td>
        </tr>
    @endif
</table>
