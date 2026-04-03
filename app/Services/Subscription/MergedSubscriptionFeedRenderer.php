<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
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
        $body = '';

        try {
            $fiResp = $this->fetchSubResponse(
                (string) ($fiNode['sub_origin'] ?? ''),
                $sub->fi_sub_id,
                (string) ($fiNode['pub_host'] ?? '')
            );
            $nlResp = $this->fetchSubResponse(
                (string) ($nlNode['sub_origin'] ?? ''),
                $sub->nl_sub_id,
                (string) ($nlNode['pub_host'] ?? '')
            );

            if (! $fiResp->successful()) {
                throw new \RuntimeException('FI подписка: HTTP '.$fiResp->status());
            }
            if (! $nlResp->successful()) {
                throw new \RuntimeException('NL подписка: HTTP '.$nlResp->status());
            }

            $fiUi = $this->parseUserinfoHeader($fiResp->header('subscription-userinfo'));
            $nlUi = $this->parseUserinfoHeader($nlResp->header('subscription-userinfo'));

            $expireSec = (int) (($sub->expiry_ms ?? 0) / 1000);
            if ($expireSec === 0) {
                $expireSec = max($fiUi['expire'] ?? 0, $nlUi['expire'] ?? 0);
            }

            $nodeTotalBytes = $sub->perNodeTotalBytes();

            $pairs = [
                [trim($fiResp->body()), $fiUi, (string) ($fiNode['vless_display_name'] ?? 'FI')],
                [trim($nlResp->body()), $nlUi, (string) ($nlNode['vless_display_name'] ?? 'NL')],
            ];

            $bodyChunks = [];
            foreach ($pairs as [$raw, $ui, $baseName]) {
                if ($raw === '') {
                    continue;
                }
                $line = VlessSubscriptionHelper::decodeSubLine($raw);
                if ($line === '') {
                    continue;
                }
                $label = $sub->vlessDisplayLabel($baseName);
                $vless = VlessSubscriptionHelper::setVlessFragment($line, $label);
                $up = (int) ($ui['upload'] ?? 0);
                $down = (int) ($ui['download'] ?? 0);
                $nodeUserinfo = $this->formatUserinfoValue($up, $down, $nodeTotalBytes, $expireSec);
                $bodyChunks[] = '#subscription-userinfo: '.$nodeUserinfo;
                $bodyChunks[] = $vless;
            }

            $body = implode("\n", $bodyChunks)."\n";
        } catch (Throwable $e) {
            return new Response('Error: '.$e->getMessage(), 502, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $quotaGb = max(1, (int) $sub->quota_gb);
        $totalCap = $quotaGb * self::BYTES_PER_GB;
        $expireSec = (int) (($sub->expiry_ms ?? 0) / 1000);
        if ($expireSec === 0) {
            $expireSec = max($fiUi['expire'] ?? 0, $nlUi['expire'] ?? 0);
        }

        $up = ($fiUi['upload'] ?? 0) + ($nlUi['upload'] ?? 0);
        $down = ($fiUi['download'] ?? 0) + ($nlUi['download'] ?? 0);
        $userinfo = $this->formatUserinfoValue($up, $down, $totalCap, $expireSec);

        if (config('xui.sub_output_b64', false)) {
            $body = base64_encode($body)."\n";
        }

        $hours = (string) config('xui.sub_profile_update_hours', '12');

        return new Response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'subscription-userinfo' => $userinfo,
            'profile-update-interval' => $hours,
        ]);
    }

    private function fetchSubResponse(string $subOrigin, string $subId, string $pubHost): \Illuminate\Http\Client\Response
    {
        $url = rtrim($subOrigin, '/').'/sub/'.$subId;

        return Http::withoutVerifying()
            ->withHeaders([
                'X-Forwarded-Host' => $pubHost,
                'X-Real-IP' => $pubHost,
                'Accept' => '*/*',
            ])
            ->timeout(45)
            ->get($url);
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
}
