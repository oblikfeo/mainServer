<?php

namespace App\Http\Controllers;

use App\Services\Account\AccountOverview;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Моя подписка»: у аккаунта одна подписка и одна ссылка.
 */
class CabinetController extends Controller
{
    public function index(Request $request): View
    {
        return view('cabinet.index', [
            'account' => AccountOverview::forUser($request->user()),
        ]);
    }
}
