<?php

namespace App\Services\Subscription;

use App\Models\AppSetting;
use App\Models\Subscription;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class MergedSubscriptionFeedRenderer
{
    private const BYTES_PER_GB = 1_073_741_824;

    public function render(Subscription $sub): Response
    {
        $nodes = config('xui.nodes', []);
        $fiNode = $nodes['fi'] ?? [];
        $nlNode = $nodes['nl'] ?? [];

        $fiUi = [];
        $nlUi = [];
        $lines = [];

        try {
            [$fiResp, $nlResp] = $this->fetchPanelSubsParallel($fiNode, $nlNode, $sub);

            if (! $fiResp->successful()) {
                throw new \RuntimeException('FI подписка: HTTP '.$fiResp->status());
            }
            if (! $nlResp->successful()) {
                throw new \RuntimeException('NL подписка: HTTP '.$nlResp->status());
            }

            $fiUi = $this->parseUserinfoHeader($fiResp->header('subscription-userinfo'));
            $nlUi = $this->parseUserinfoHeader($nlResp->header('subscription-userinfo'));

            $pairs = [
                [
                    'raw' => trim($fiResp->body()),
                    'label' => 'FI',
                    'name' => (string) ($fiNode['vless_display_name'] ?? 'FI'),
                ],
                [
                    'raw' => trim($nlResp->body()),
                    'label' => 'NL',
                    'name' => (string) ($nlNode['vless_display_name'] ?? 'NL'),
                ],
            ];

            foreach ($pairs as $row) {
                $raw = $row['raw'];
                if ($raw === '') {
                    throw new \RuntimeException($row['label'].': пустой ответ панели по ссылке sub');
                }
                $line = VlessSubscriptionHelper::extractVlessLineFromSubscriptionBody($raw);
                if ($line === '' || ! str_starts_with($line, 'vless://')) {
                    throw new \RuntimeException(
                        $row['label'].': в ответе нет vless:// (часто из‑за строк #meta в теле подписки панели — уже обрабатывается; проверьте subId и доступность sub_origin с сервера Laravel)'
                    );
                }
                $lines[] = VlessSubscriptionHelper::setVlessFragment(
                    $line,
                    $row['name'],
                    (string) config('xui.vless_server_description', '')
                );
            }
        } catch (Throwable $e) {
            Log::warning('subscription.feed.error', [
                'message' => $e->getMessage(),
                'token_tail' => substr($sub->token, -6),
            ]);

            return new Response('Error: '.$e->getMessage(), 503, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Retry-After' => '30',
            ]);
        }

        $body = implode("\n", array_filter($lines))."\n";

        $quotaGb = max(1, (int) $sub->quota_gb);
        $totalCap = $quotaGb * self::BYTES_PER_GB;
        $expireSec = (int) (($sub->expiry_ms ?? 0) / 1000);
        if ($expireSec === 0) {
            $expireSec = max($fiUi['expire'] ?? 0, $nlUi['expire'] ?? 0);
        }

        $up = ($fiUi['upload'] ?? 0) + ($nlUi['upload'] ?? 0);
        $down = ($fiUi['download'] ?? 0) + ($nlUi['download'] ?? 0);
        $userinfo = $this->formatUserinfoValue($up, $down, $totalCap, $expireSec);

        $profileTitle = $this->profileTitleForHapp();
        $meta = "#profile-title: {$profileTitle}\n#subscription-userinfo: {$userinfo}\n";

        if (config('xui.sub_output_b64', false)) {
            $body = base64_encode($meta.$body)."\n";
        } else {
            $body = $meta.$body;
        }

        $hours = (string) config('xui.sub_profile_update_hours', '12');

        // Имя профиля только в теле (#profile-title) — в HTTP-заголовке UTF-8/прокси часто ломают ответ.
        return new Response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'subscription-userinfo' => $userinfo,
            'profile-update-interval' => $hours,
        ]);
    }

    /**
     * Параллельно FI+NL: два последовательных запроса часто упираются в proxy_read_timeout nginx → «502» без тела.
     *
     * @param  array<string, mixed>  $fiNode
     * @param  array<string, mixed>  $nlNode
     * @return array{0: \Illuminate\Http\Client\Response, 1: \Illuminate\Http\Client\Response}
     */
    private function fetchPanelSubsParallel(array $fiNode, array $nlNode, Subscription $sub): array
    {
        $fiOrigin = rtrim((string) ($fiNode['sub_origin'] ?? ''), '/');
        $nlOrigin = rtrim((string) ($nlNode['sub_origin'] ?? ''), '/');
        if ($fiOrigin === '' || $nlOrigin === '') {
            throw new \RuntimeException('Пустой XUI_FI_SUB_ORIGIN или XUI_NL_SUB_ORIGIN в .env');
        }

        $fiUrl = $fiOrigin.'/sub/'.rawurlencode((string) $sub->fi_sub_id);
        $nlUrl = $nlOrigin.'/sub/'.rawurlencode((string) $sub->nl_sub_id);

        $fiHeaders = $this->panelSubHeaders((string) ($fiNode['pub_host'] ?? ''));
        $nlHeaders = $this->panelSubHeaders((string) ($nlNode['pub_host'] ?? ''));

        $responses = Http::pool(fn (Pool $pool) => [
            $pool->as('fi')
                ->withoutVerifying()
                ->withHeaders($fiHeaders)
                ->connectTimeout(12)
                ->timeout(28)
                ->get($fiUrl),
            $pool->as('nl')
                ->withoutVerifying()
                ->withHeaders($nlHeaders)
                ->connectTimeout(12)
                ->timeout(28)
                ->get($nlUrl),
        ]);

        return [$responses['fi'], $responses['nl']];
    }

    /**
     * Пустые X-Forwarded-* ломают часть инсталляций 3x-ui/nginx; не шлём, если pub_host не задан.
     *
     * @return array<string, string>
     */
    private function panelSubHeaders(string $pubHost): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
            'Accept' => '*/*',
            'Accept-Encoding' => 'identity',
        ];
        $pubHost = trim($pubHost);
        if ($pubHost !== '') {
            $headers['X-Forwarded-Host'] = $pubHost;
            $headers['X-Real-IP'] = $pubHost;
        }

        return $headers;
    }

    /**
     * @return array<string, int>
     */
    private function parseUserinfoHeader(?string $val): array
    {
        if ($val === null || $val === '') {
            return [];
        }

        $out = [];
        foreach (explode(';', $val) as $part) {
            $part = trim($part);
            if (! str_contains($part, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $part, 2);
            $k = trim($k);
            $v = trim($v);
            $out[$k] = (int) round((float) $v);
        }

        return $out;
    }

    private function formatUserinfoValue(int $upload, int $download, int $total, int $expireSec): string
    {
        return "upload={$upload}; download={$download}; total={$total}; expire={$expireSec}";
    }

    private function profileTitleForHapp(): string
    {
        $fromDb = null;
        try {
            $fromDb = AppSetting::getValue('happ_profile_title');
        } catch (Throwable) {
        }

        $raw = trim((string) ($fromDb !== null && $fromDb !== '' ? $fromDb : config('xui.sub_profile_title', 'nadezhda VPN')));
        if ($raw === '') {
            return 'nadezhda VPN';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($raw, 0, 25);
        }

        return substr($raw, 0, 25);
    }
}
