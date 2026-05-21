<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ad-hoc test subscription feeds. Isolated from the production pipeline:
 * no DB, no 3x-ui, no HWID, no quotas. Lives entirely in config/test_subscriptions.php.
 *
 * Used to hand out 6 raw vless:// to specific testers via Happ subscription URL.
 */
final class TestSubscriptionFeedController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $cfg = config('test_subscriptions.tokens', []);
        if (! is_array($cfg) || ! isset($cfg[$token]) || ! is_array($cfg[$token])) {
            abort(404);
        }

        $entry = $cfg[$token];
        $lines = array_values(array_filter(
            array_map(static fn ($v): string => trim((string) $v), $entry['lines'] ?? []),
            static fn (string $v): bool => $v !== '' && str_starts_with($v, 'vless://'),
        ));

        if ($lines === []) {
            abort(404);
        }

        $title = $this->shortenForHapp((string) ($entry['title'] ?? 'Test'), 25);
        $note = trim((string) ($entry['note'] ?? ''));

        // Far-future expire and a huge cap so Happ doesn't show "expired" or "quota exhausted".
        $expireSec = now()->addYear()->timestamp;
        $userinfo = "upload=0; download=0; total=1099511627776; expire={$expireSec}";

        $metaLines = [
            '#profile-title: '.$title,
            '#subscription-userinfo: '.$userinfo,
        ];
        if ($note !== '') {
            $metaLines[] = '# '.$note;
        }

        $body = implode("\n", array_merge($metaLines, $lines))."\n";
        if (filter_var(config('test_subscriptions.output_b64', false), FILTER_VALIDATE_BOOL)) {
            $body = base64_encode($body)."\n";
        }

        $hours = (string) config('test_subscriptions.profile_update_hours', 6);

        return new Response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'subscription-userinfo' => $userinfo,
            'profile-update-interval' => $hours,
            'Cache-Control' => 'no-store',
        ]);
    }

    private function shortenForHapp(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'Test';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }
}
