<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\Subscription\MergedSubscriptionFeedRenderer;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionFeedController extends Controller
{
    public function show(string $token, MergedSubscriptionFeedRenderer $renderer): Response
    {
        $subscription = Subscription::query()->where('token', $token)->first();
        if ($subscription === null) {
            abort(404);
        }

        return $renderer->render($subscription);
    }
}
