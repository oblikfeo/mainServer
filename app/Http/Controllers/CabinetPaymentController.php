<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CabinetPaymentController extends Controller
{
    public function __invoke(): View
    {
        return view('cabinet.payment');
    }
}
