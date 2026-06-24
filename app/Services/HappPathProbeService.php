<?php

namespace App\Services;

use App\Services\Subscription\SubscriptionExtraShareLines;
use App\Services\Subscription\VlessUriToXrayOutbound;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class HappPathProbeService
{
    private const CACHE_KEY = 'happ_path_probe_v2';

    /**
     * Только чтение кэша — для GET страницы (без прогона probe).
     *
     * @return array{
     *     checked_at: string|null,
     *     xray_available: bool|null,
     *     rows: list<array<string, mixed>>,
     *     from_cache: bool,
     * }
     */
    public function forPage(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && isset($cached['rows'])) {
            $cached['from_cache'] = true;

            return $cached;
        }

        return [
            'checked_at' => null,
            'xray_available' => null,
            'rows' => [],
            'from_cache' => false,
        ];
    }

    /**
     * Полный прогон + запись в кэш (кнопка «Проверить», artisan --refresh).
     *
     * @return array{
     *     checked_at: string|null,
     *     xray_available: bool,
     *     rows: list<array<string, mixed>>,
     *     from_cache: bool,
     * }
     */
    public function refreshAndCache(): array
    {
        $results = $this->runAll();
        $results['from_cache'] = true;
        Cache::put(self::CACHE_KEY, $results, max(10, (int) config('path_probe.cache_ttl', 120)));

        return $results;
    }

    /**
     * @return array{
     *     checked_at: string|null,
     *     xray_available: bool,
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function runAll(): array
    {
        $xrayBin = $this->xrayBinary();
        $xrayAvailable = $xrayBin !== null;

        $rows = [];

        foreach ($this->configuredTargets() as $target) {
            if (! $xrayAvailable) {
                $rows[] = $this->skippedVpnRow($target, 'Xray не найден на hub');

                continue;
            }

            try {
                $rows[] = $this->probeVpnNode($target, $xrayBin);
            } catch (Throwable $e) {
                Log::warning('happ path probe failed', [
                    'node' => $target['id'],
                    'error' => $e->getMessage(),
                ]);
                $rows[] = $this->failedVpnRow($target, $e->getMessage());
            }
        }

        foreach ((array) config('path_probe.web_pages', []) as $page) {
            if (! is_array($page)) {
                continue;
            }

            $rows[] = $this->probeWebPage($page);
        }

        return [
            'checked_at' => now()->toIso8601String(),
            'xray_available' => $xrayAvailable,
            'rows' => $rows,
        ];
    }

    /**
     * @return list<array{id: string, title: string, vless_uri: string}>
     */
    public function configuredTargets(): array
    {
        $out = [];

        foreach ((array) config('path_probe.nodes', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            $extraKey = trim((string) ($row['extra_key'] ?? ''));
            if ($id === '' || $extraKey === '') {
                continue;
            }

            $extra = config('xui.'.$extraKey, []);
            if (! is_array($extra) || ! SubscriptionExtraShareLines::isConfigured($extra)) {
                continue;
            }

            $uri = trim((string) ($extra['vless_uri'] ?? ''));
            if ($uri === '' || ! str_starts_with($uri, 'vless://')) {
                continue;
            }

            $title = trim((string) ($extra['vless_title'] ?? $id));

            $out[] = [
                'id' => $id,
                'title' => $title !== '' ? $title : strtoupper($id),
                'vless_uri' => explode('#', $uri, 2)[0],
            ];
        }

        return $out;
    }

    /**
     * @param  array{id: string, title: string, vless_uri: string}  $target
     * @return array<string, mixed>|null
     */
    public function buildClientConfig(array $target, int $socksPort): ?array
    {
        $outbound = VlessUriToXrayOutbound::convert($target['vless_uri'], 'proxy');
        if ($outbound === null) {
            return null;
        }

        return [
            'log' => ['loglevel' => 'warning'],
            'inbounds' => [
                [
                    'listen' => '127.0.0.1',
                    'port' => $socksPort,
                    'protocol' => 'socks',
                    'settings' => [
                        'auth' => 'noauth',
                        'udp' => true,
                    ],
                    'tag' => 'socks-in',
                ],
            ],
            'outbounds' => [
                $outbound,
                ['protocol' => 'freedom', 'tag' => 'direct'],
                ['protocol' => 'blackhole', 'tag' => 'block'],
            ],
            'routing' => [
                'domainStrategy' => 'AsIs',
                'rules' => [
                    [
                        'type' => 'field',
                        'inboundTag' => ['socks-in'],
                        'outboundTag' => 'proxy',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array{id: string, title: string, vless_uri: string}  $target
     * @return array<string, mixed>
     */
    private function probeVpnNode(array $target, string $xrayBin): array
    {
        $socksPort = max(1024, (int) config('path_probe.socks_port', 10820));
        $config = $this->buildClientConfig($target, $socksPort);

        if ($config === null) {
            return $this->failedVpnRow($target, 'Не удалось собрать outbound из vless://');
        }

        $dir = storage_path('app/path-probe');
        File::ensureDirectoryExists($dir);
        $configPath = $dir.'/probe-'.$target['id'].'.json';
        File::put($configPath, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

        $process = Process::timeout(max(30, (int) config('path_probe.probe_timeout_seconds', 90)))
            ->start([$xrayBin, 'run', '-config', $configPath]);

        try {
            usleep((int) (max(0.5, (float) config('path_probe.xray_startup_seconds', 2.5)) * 1_000_000));

            if (! $process->running()) {
                return $this->failedVpnRow($target, trim($process->errorOutput()) ?: 'Xray завершился сразу после старта');
            }

            $trace = $this->curlThroughSocks($socksPort, (string) config('path_probe.trace_url', ''), true);
            if (! ($trace['ok'] ?? false)) {
                return $this->assembleVpnRow($target, false, $trace, null);
            }

            $speed = $this->measureDownloadSpeed($socksPort);

            return $this->assembleVpnRow($target, true, $trace, $speed);
        } finally {
            $this->stopProcess($process);
        }
    }

    /**
     * @param  array{id?: string, title?: string, url?: string}  $page
     * @return array<string, mixed>
     */
    private function probeWebPage(array $page): array
    {
        $id = trim((string) ($page['id'] ?? ''));
        $title = trim((string) ($page['title'] ?? $id));
        $url = trim((string) ($page['url'] ?? ''));

        if ($url === '') {
            return $this->webRow($id, $title, $url, 'skip', null, 'URL не задан');
        }

        $result = Process::timeout(20)->run([
            'curl',
            '-sS',
            '--connect-timeout', '8',
            '--max-time', '15',
            '-o', '/dev/null',
            '-w', '%{http_code} %{time_total}',
            $url,
        ]);

        $parts = preg_split('/\s+/', trim($result->output()), 2) ?: [];
        $httpCode = isset($parts[0]) ? (int) $parts[0] : 0;
        $seconds = isset($parts[1]) ? (float) $parts[1] : 0.0;
        $ok = $result->successful() && $httpCode >= 200 && $httpCode < 400;

        return $this->webRow(
            $id,
            $title !== '' ? $title : $id,
            $url,
            $ok ? 'ok' : 'fail',
            $ok ? (int) round($seconds * 1000) : null,
            $ok ? null : (trim($result->errorOutput()) ?: ($httpCode > 0 ? 'HTTP '.$httpCode : 'не открывается')),
        );
    }

    /**
     * @param  array<string, mixed>  $trace
     * @param  array<string, mixed>|null  $speed
     * @return array<string, mixed>
     */
    private function assembleVpnRow(array $target, bool $tunnelOk, array $trace, ?array $speed): array
    {
        $egressIp = isset($trace['egress_ip']) ? (string) $trace['egress_ip'] : null;
        $egressOk = $this->evaluateEgress($target['id'], $egressIp);

        $status = 'fail';
        if ($tunnelOk) {
            $status = $egressOk === false ? 'warn' : 'ok';
        }

        return [
            'kind' => 'vpn',
            'id' => $target['id'],
            'title' => $target['title'],
            'status' => $status,
            'latency_ms' => isset($trace['total_ms']) ? (int) $trace['total_ms'] : null,
            'download_mbps' => is_array($speed) ? ($speed['mbps'] ?? null) : null,
            'egress_ip' => $egressIp,
            'egress_colo' => isset($trace['egress_colo']) ? (string) $trace['egress_colo'] : null,
            'error' => $tunnelOk ? null : (string) ($trace['error'] ?? 'канал не поднялся'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function webRow(
        string $id,
        string $title,
        string $url,
        string $status,
        ?int $latencyMs,
        ?string $error,
    ): array {
        return [
            'kind' => 'web',
            'id' => $id,
            'title' => $title,
            'url' => $url,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'download_mbps' => null,
            'egress_ip' => null,
            'egress_colo' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array{ok: bool, error?: string, egress_ip?: string, egress_colo?: string, total_ms?: int}
     */
    private function curlThroughSocks(int $socksPort, string $url, bool $captureBody): array
    {
        if ($url === '') {
            return ['ok' => false, 'error' => 'Пустой URL проверки'];
        }

        $result = Process::timeout(25)->run([
            'curl',
            '-fsS',
            '--connect-timeout', '12',
            '--max-time', '22',
            '--socks5-hostname', "127.0.0.1:{$socksPort}",
            '-w', '\n%{time_total}',
            $url,
        ]);

        if (! $result->successful()) {
            return [
                'ok' => false,
                'error' => trim($result->errorOutput()) ?: trim($result->output()) ?: 'curl error',
            ];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($result->output())) ?: [];
        $totalSeconds = (float) ($lines[array_key_last($lines)] ?? 0);
        $body = implode("\n", array_slice($lines, 0, max(0, count($lines) - 1)));
        $trace = $captureBody ? $this->parseTraceBody($body) : [];

        return [
            'ok' => true,
            'egress_ip' => $trace['ip'] ?? null,
            'egress_colo' => $trace['colo'] ?? null,
            'total_ms' => (int) round($totalSeconds * 1000),
        ];
    }

    /**
     * @return array{ip?: string, colo?: string}
     */
    private function parseTraceBody(string $body): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            if (! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $key = trim($k);
            if ($key === 'ip' || $key === 'colo') {
                $out[$key] = trim($v);
            }
        }

        return $out;
    }

    /**
     * @return array{mbps: float|null}|null
     */
    private function measureDownloadSpeed(int $socksPort): ?array
    {
        $url = trim((string) config('path_probe.speed_url', ''));
        if ($url === '') {
            return null;
        }

        $result = Process::timeout(30)->run([
            'curl',
            '-fsS',
            '--connect-timeout', '10',
            '--max-time', '20',
            '--socks5-hostname', "127.0.0.1:{$socksPort}",
            '-o', '/dev/null',
            '-w', '%{size_download} %{time_total}',
            $url,
        ]);

        if (! $result->successful()) {
            return ['mbps' => null];
        }

        $parts = preg_split('/\s+/', trim($result->output())) ?: [];
        if (count($parts) < 2) {
            return ['mbps' => null];
        }

        $bytes = (int) $parts[0];
        $seconds = (float) $parts[1];
        if ($bytes < 1 || $seconds <= 0) {
            return ['mbps' => null];
        }

        return ['mbps' => round(($bytes * 8) / ($seconds * 1_000_000), 1)];
    }

    private function evaluateEgress(string $nodeId, ?string $egressIp): ?bool
    {
        if ($egressIp === null || $egressIp === '') {
            return null;
        }

        $rules = config('path_probe.egress_rules.'.$nodeId, []);
        if (! is_array($rules)) {
            return null;
        }

        $expected = trim((string) ($rules['expected_egress'] ?? ''));
        $forbidden = trim((string) ($rules['must_not_egress'] ?? ''));

        if ($forbidden !== '' && $egressIp === $forbidden) {
            return false;
        }

        if ($expected !== '') {
            return $egressIp === $expected;
        }

        return true;
    }

    /**
     * @param  array{id: string, title: string}  $target
     * @return array<string, mixed>
     */
    private function skippedVpnRow(array $target, string $reason): array
    {
        return [
            'kind' => 'vpn',
            'id' => $target['id'],
            'title' => $target['title'],
            'status' => 'skip',
            'latency_ms' => null,
            'download_mbps' => null,
            'egress_ip' => null,
            'egress_colo' => null,
            'error' => $reason,
        ];
    }

    /**
     * @param  array{id: string, title: string}  $target
     * @return array<string, mixed>
     */
    private function failedVpnRow(array $target, string $reason): array
    {
        $row = $this->skippedVpnRow($target, $reason);
        $row['status'] = 'fail';

        return $row;
    }

    private function xrayBinary(): ?string
    {
        $configured = trim((string) config('path_probe.xray_binary', '/usr/local/bin/xray'));
        $candidates = array_values(array_unique(array_filter([
            $configured,
            '/usr/local/bin/xray',
            '/usr/bin/xray',
        ])));

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        $which = Process::timeout(3)->run(['bash', '-lc', 'command -v xray']);
        if ($which->successful()) {
            $path = trim($which->output());
            if ($path !== '' && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function stopProcess(InvokedProcess $process): void
    {
        if (! $process->running()) {
            return;
        }

        try {
            $process->signal(SIGTERM);
        } catch (Throwable) {
            //
        }

        $deadline = microtime(true) + 3;
        while ($process->running() && microtime(true) < $deadline) {
            usleep(100_000);
        }

        if ($process->running()) {
            try {
                $process->signal(SIGKILL);
            } catch (Throwable) {
                //
            }
        }
    }
}
