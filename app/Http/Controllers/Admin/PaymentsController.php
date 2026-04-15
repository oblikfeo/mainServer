<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentsController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $q = Purchase::query()->with('user')->orderByDesc('paid_at');
        $ordersQ = PaymentOrder::query()->with('user')->orderByDesc('id');

        if (is_string($dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $q->whereDate('paid_at', '>=', $dateFrom);
            $ordersQ->whereDate('created_at', '>=', $dateFrom);
        }
        if (is_string($dateTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $q->whereDate('paid_at', '<=', $dateTo);
            $ordersQ->whereDate('created_at', '<=', $dateTo);
        }

        $purchases = $q->paginate(30)->withQueryString();
        $orders = $ordersQ->paginate(30, pageName: 'orders_page')->withQueryString();

        $statsQ = Purchase::query();
        if (is_string($dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $statsQ->whereDate('paid_at', '>=', $dateFrom);
        }
        if (is_string($dateTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $statsQ->whereDate('paid_at', '<=', $dateTo);
        }

        $totalRub = (int) $statsQ->sum('amount_rub');
        $totalCount = (int) $statsQ->count();

        return view('admin.payments', [
            'purchases' => $purchases,
            'orders' => $orders,
            'dateFrom' => is_string($dateFrom) ? $dateFrom : '',
            'dateTo' => is_string($dateTo) ? $dateTo : '',
            'totalRub' => $totalRub,
            'totalCount' => $totalCount,
        ]);
    }
}

