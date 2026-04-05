<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = $request->user()
            ->purchases()
            ->orderByDesc('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('cabinet.purchases', [
            'purchases' => $purchases,
        ]);
    }
}
