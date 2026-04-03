<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function create(): View
    {
        return view('admin.subscription.create');
    }
}
