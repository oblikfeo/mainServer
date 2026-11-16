<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\AccountOverview;
use App\Services\Account\DeviceSlots;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * «Клиенты и платежи»: одна страница с поиском — вкладка «Платежи» (кто, сколько, что получил)
 * и вкладка «Клиенты»; по клику — карточка клиента (подписка, устройства, все оплаты).
 * Заменяет прежние «Отчёт» и «Платежи».
 */
class ClientsController extends Controller
{
    /** Время в админке — по Омску. */
    public const TZ = 'Asia/Omsk';

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'clients' ? 'clients' : 'payments';
        $q = trim((string) $request->query('q', ''));
        $userIds = $q !== '' ? $this->matchUserIds($q) : null;

        if ($tab === 'payments') {
            $rows = Purchase::query()
                ->with('user')
                ->when($userIds !== null, fn (Builder $b) => $b->where(function (Builder $w) use ($userIds, $q) {
                    $w->whereIn('user_id', $userIds)->orWhere('description', 'like', '%'.$this->escapeLike($q).'%');
                }))
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->paginate(50)
                ->withQueryString();
        } else {
            $rows = User::query()
                ->withSum('purchases as paid_rub', 'amount_rub')
                ->withCount('purchases as paid_count')
                ->withMax('purchases as last_paid_at', 'paid_at')
                ->when($userIds !== null, fn (Builder $b) => $b->whereIn('id', $userIds))
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString();
        }

        $subs = $this->subscriptionsFor($rows->getCollection()->map(fn ($r) => $tab === 'payments' ? $r->user_id : $r->id));

        return view('admin.clients.index', [
            'tab' => $tab,
            'q' => $q,
            'rows' => $rows,
            'subs' => $subs,
            'nowMs' => DeviceSlots::nowMs(),
        ]);
    }

    public function show(User $user): View
    {
        $account = AccountOverview::forUser($user);
        $orders = PaymentOrder::query()->where('user_id', $user->id)->orderByDesc('id')->limit(100)->get();
        $purchases = Purchase::query()->where('user_id', $user->id)->orderByDesc('paid_at')->get();
        $referrer = $user->referred_by ? User::query()->find($user->referred_by) : null;
        $invited = User::query()->where('referred_by', $user->id)->count();

        return view('admin.clients.show', [
            'user' => $user,
            'account' => $account,
            'orders' => $orders,
            'purchases' => $purchases,
            'referrer' => $referrer,
            'invited' => $invited,
        ]);
    }

    /**
     * Поиск: почта, ник (name), Telegram (@ник или id), номер подписки (#12345), номер заказа (ord_…).
     *
     * @return list<int>
     */
    private function matchUserIds(string $q): array
    {
        $term = ltrim($q, '@#');
        $like = '%'.$this->escapeLike(mb_strtolower($term)).'%';

        $ids = User::query()
            ->where(function (Builder $w) use ($like, $term) {
                $w->whereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(telegram_username) LIKE ?', [$like]);
                if (ctype_digit($term)) {
                    $w->orWhere('telegram_id', (int) $term)->orWhere('telegram_user_id', (int) $term)->orWhere('id', (int) $term);
                }
            })
            ->pluck('id');

        if (ctype_digit($term)) {
            $ids = $ids->merge(Subscription::query()->where('public_code', (int) $term)->whereNotNull('user_id')->pluck('user_id'));
        }
        if (str_starts_with($term, 'ord_')) {
            $ids = $ids->merge(PaymentOrder::query()->where('order_id', $term)->whereNotNull('user_id')->pluck('user_id'));
        }

        return $ids->unique()->map(fn ($id) => (int) $id)->values()->all();
    }

    /** @return Collection<int, Subscription> подписка аккаунта по user_id */
    private function subscriptionsFor(Collection $userIds): Collection
    {
        $ids = $userIds->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return Subscription::query()
            ->with('deviceSlots')
            ->whereIn('user_id', $ids)
            ->orderBy('id')
            ->get()
            ->keyBy('user_id'); // у аккаунта одна подписка; при дублях берётся последняя
    }

    /** Плашка состояния подписки: [подпись, классы]. */
    public static function state(?Subscription $sub, ?int $nowMs = null): array
    {
        if ($sub === null) {
            return ['нет подписки', 'bg-slate-100 text-slate-500'];
        }
        if (! DeviceSlots::subscriptionActive($sub, $nowMs)) {
            return ['закончилась', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'];
        }

        return $sub->is_trial
            ? ['пробная', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200']
            : ['активна', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'];
    }

    public static function rub(mixed $n): string
    {
        return number_format((int) $n, 0, ',', ' ').' ₽';
    }

    public static function omsk(mixed $dt): string
    {
        return $dt ? \Illuminate\Support\Carbon::parse($dt)->timezone(self::TZ)->format('d.m.Y H:i') : '—';
    }

    public static function nick(?User $u): string
    {
        return $u ? (string) ($u->name ?: \Illuminate\Support\Str::before((string) $u->email, '@')) : '—';
    }

    private function escapeLike(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }
}
