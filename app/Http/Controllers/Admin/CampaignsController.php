<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Воронка рекламных кампаний: t.me/{bot}?start=МЕТКА → регистрация → триал → оплата.
 *
 * Период фильтрует момент прихода человека (клик по ссылке / создание аккаунта),
 * а выручка считается по всем оплатам пришедшей когорты — иначе оплата на второй
 * месяц выпадала бы из отчёта по кампании, которая её привела.
 */
final class CampaignsController extends Controller
{
    public function index(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->range($request);

        $rows = $this->rows($dateFrom, $dateTo);

        return view('admin.campaigns', [
            'rows' => $rows,
            'totals' => $this->totals($rows),
            'breakdown' => $this->breakdown($dateFrom, $dateTo),
            'dateFrom' => $dateFrom ?? '',
            'dateTo' => $dateTo ?? '',
            'botUsername' => trim((string) config('telegram.link_bot_username', '')),
            'windowHours' => (int) config('campaign.attribution_window_hours', 72),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$dateFrom, $dateTo] = $this->range($request);
        $rows = $this->rows($dateFrom, $dateTo);

        $name = 'campaigns-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'wb');
            // BOM — иначе Excel открывает кириллицу кракозябрами.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Кампания', 'Переходы', 'Регистрации', 'Триалы', 'Покупатели', 'Оплат', 'Выручка', 'Конверсия %'], ';');
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['campaign'],
                    $row['clicks'],
                    $row['registrations'],
                    $row['trials'],
                    $row['buyers'],
                    $row['orders'],
                    $row['revenue'],
                    $row['conversion'] === null ? '' : number_format($row['conversion'], 1, ',', ''),
                ], ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function range(Request $request): array
    {
        return [
            $this->date($request->query('date_from')),
            $this->date($request->query('date_to')),
        ];
    }

    private function date(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    /**
     * @return list<array{campaign: string, clicks: int, registrations: int, trials: int, buyers: int, orders: int, revenue: int, conversion: ?float}>
     */
    private function rows(?string $from, ?string $to): array
    {
        $clicks = $this->clicks($from, $to);
        $registrations = $this->registrations($from, $to);
        $trials = $this->trials($from, $to);
        $money = $this->money($from, $to);

        $campaigns = array_values(array_unique(array_merge(
            array_keys($clicks),
            array_keys($registrations),
        )));

        $rows = [];
        foreach ($campaigns as $campaign) {
            $campaign = (string) $campaign;
            $clickCount = (int) ($clicks[$campaign] ?? 0);
            $buyers = (int) ($money[$campaign]->buyers ?? 0);

            $rows[] = [
                'campaign' => $campaign,
                'clicks' => $clickCount,
                'registrations' => (int) ($registrations[$campaign] ?? 0),
                'trials' => (int) ($trials[$campaign] ?? 0),
                'buyers' => $buyers,
                'orders' => (int) ($money[$campaign]->orders ?? 0),
                'revenue' => (int) ($money[$campaign]->revenue ?? 0),
                'conversion' => $clickCount > 0 ? round($buyers * 100 / $clickCount, 1) : null,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => [$b['revenue'], $b['clicks'], $b['registrations']]
            <=> [$a['revenue'], $a['clicks'], $a['registrations']]);

        return $rows;
    }

    /**
     * Переходы по ссылке — уникальные Telegram-аккаунты, нажавшие /start с меткой,
     * включая тех, кто до регистрации не дошёл.
     *
     * @return array<string, int>
     */
    private function clicks(?string $from, ?string $to): array
    {
        $q = DB::table('telegram_start_utm_logs')
            ->whereNotExists(fn (Builder $sub) => $sub
                ->select(DB::raw('1'))
                ->from('users')
                ->whereColumn('users.referral_code', 'telegram_start_utm_logs.utm_param'))
            ->groupBy('utm_param')
            ->selectRaw('utm_param as campaign, count(distinct telegram_user_id) as cnt');

        $this->applyDates($q, 'telegram_start_utm_logs.created_at', $from, $to);

        return $q->pluck('cnt', 'campaign')
            ->map(static fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function registrations(?string $from, ?string $to): array
    {
        $q = $this->attributedUsers()
            ->groupBy('users.utm_campaign')
            ->selectRaw('users.utm_campaign as campaign, count(*) as cnt');

        $this->applyDates($q, 'users.created_at', $from, $to);

        return $q->pluck('cnt', 'campaign')
            ->map(static fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function trials(?string $from, ?string $to): array
    {
        $q = $this->attributedUsers()
            ->join('subscriptions', 'subscriptions.user_id', '=', 'users.id')
            ->where('subscriptions.is_trial', true)
            ->groupBy('users.utm_campaign')
            ->selectRaw('users.utm_campaign as campaign, count(distinct users.id) as cnt');

        $this->applyDates($q, 'users.created_at', $from, $to);

        return $q->pluck('cnt', 'campaign')
            ->map(static fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Покупатели, число оплат и выручка когорты.
     *
     * @return array<string, object>
     */
    private function money(?string $from, ?string $to): array
    {
        $q = $this->attributedUsers()
            ->join('payment_orders', 'payment_orders.user_id', '=', 'users.id')
            ->where('payment_orders.status', 'paid')
            ->groupBy('users.utm_campaign')
            ->selectRaw('users.utm_campaign as campaign, count(distinct users.id) as buyers, count(*) as orders, coalesce(sum(payment_orders.amount_rub), 0) as revenue');

        $this->applyDates($q, 'users.created_at', $from, $to);

        return $q->get()->keyBy('campaign')->all();
    }

    /**
     * Что именно покупали: разрез кампания × тариф × период.
     *
     * @return array<string, list<object>>
     */
    private function breakdown(?string $from, ?string $to): array
    {
        $q = $this->attributedUsers()
            ->join('payment_orders', 'payment_orders.user_id', '=', 'users.id')
            ->where('payment_orders.status', 'paid')
            ->groupBy('users.utm_campaign', 'payment_orders.tariff_plan', 'payment_orders.tariff_period')
            ->selectRaw('users.utm_campaign as campaign, payment_orders.tariff_plan as plan, payment_orders.tariff_period as period, count(*) as orders, coalesce(sum(payment_orders.amount_rub), 0) as revenue')
            ->orderByDesc('revenue');

        $this->applyDates($q, 'users.created_at', $from, $to);

        return $q->get()
            ->groupBy('campaign')
            ->map(static fn ($group) => $group->values()->all())
            ->all();
    }

    private function attributedUsers(): Builder
    {
        return DB::table('users')
            ->whereNotNull('users.utm_campaign')
            ->where('users.utm_campaign', '!=', '');
    }

    private function applyDates(Builder $query, string $column, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to !== null) {
            $query->whereDate($column, '<=', $to);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int|float|null>
     */
    private function totals(array $rows): array
    {
        $clicks = (int) array_sum(array_column($rows, 'clicks'));
        $buyers = (int) array_sum(array_column($rows, 'buyers'));

        return [
            'campaigns' => count($rows),
            'clicks' => $clicks,
            'registrations' => (int) array_sum(array_column($rows, 'registrations')),
            'trials' => (int) array_sum(array_column($rows, 'trials')),
            'buyers' => $buyers,
            'orders' => (int) array_sum(array_column($rows, 'orders')),
            'revenue' => (int) array_sum(array_column($rows, 'revenue')),
            'conversion' => $clicks > 0 ? round($buyers * 100 / $clicks, 1) : null,
        ];
    }
}
