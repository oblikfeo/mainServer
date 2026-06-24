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
    private const CACHE_KEY = 'happ_path_probe_v1';

    /**
     * @return array{
     *     checked_at: string|null,
     *     xray_available: bool,
     *     nodes: list<array<string, mixed>>,
     * }
     */
    public function cachedResults(bool $refresh = false): array
    {
        if ($refresh) {
            $results = $this->runAll();
            Cache::put(self::CACHE_KEY, $results, max(10, (int) config('path_probe.cache_ttl', 120)));

            return $results;
        }

        $ttl = max(10, (int) config('path_probe.cache_ttl', 120));

        return Cache::remember(self::CACHE_KEY, $ttl, fn (): array => $this->runAll());
    }

    /**
     * @return array{
     *     checked_at: string|null,
     *     xray_available: bool,
     *     nodes: list<array<string, mixed>>,
     * }
     */
    public function runAll(): array
    {
        $xrayBin = $this->xrayBinary();
        $xrayAvailable = $xrayBin !== null;

        $nodes = [];
        foreach ($this->configuredTargets() as $target) {
            if (! $xrayAvailable) {
                $nodes[] = $this->skippedNode($target, 'Xray не найден на hub ('.((string) config('path_probe.xray_binary')).')');

                continue;
            }

            try {
                $nodes[] = $this->probeNode($target, $xrayBin);
            } catch (Throwable $e) {
                Log::warning('happ path probe failed', [
                    'node' => $target['id'],
                    'error' => $e->getMessage(),
                ]);
                $nodes[] = $this->failedNode($target, $e->getMessage());
            }
        }

        return [
            'checked_at' => now()->toIso8601String(),
            'xray_available' => $xrayAvailable,
            'nodes' => $nodes,
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
     * @return array<string, mixed>
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
    private function probeNode(array $target, string $xrayBin): array
    {
        $started = microtime(true);
        $socksPort = max(1024, (int) config('path_probe.socks_port', 10820));
        $config = $this->buildClientConfig($target, $socksPort);

        if ($config === null) {
            return $this->failedNode($target, 'Не удалось собрать outbound из vless://');
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
                return $this->failedNode($target, trim($process->errorOutput()) ?: 'Xray завершился сразу после старта');
            }

            $trace = $this->curlThroughSocks($socksPort, (string) config('path_probe.trace_url', ''), true);
            if (! ($trace['ok'] ?? false)) {
                return $this->assembleNodeResult($target, false, $trace, null, [], $started);
            }

            $speed = $this->measureDownloadSpeed($socksPort);
            $sites = $this->probeSites($socksPort);

            return $this->assembleNodeResult($target, true, $trace, $speed, $sites, $started);
        } finally {
            $this->stopProcess($process);
        }
    }

    /**
     * @param  array<string, mixed>  $trace
     * @param  array<string, mixed>|null  $speed
     * @param  list<array<string, mixed>>  $sites
     * @return array<string, mixed>
     */
    private function assembleNodeResult(
        array $target,
        bool $tunnelOk,
        array $trace,
        ?array $speed,
        array $sites,
        float $startedAt,
    ): array {
        $egressIp = isset($trace['egress_ip']) ? (string) $trace['egress_ip'] : null;
        $egressOk = $this->evaluateEgress($target['id'], $egressIp);
        $sitesOk = $this->sitesHealthy($sites);

        $status = 'fail';
        if ($tunnelOk) {
            $status = ($egressOk !== false && $sitesOk !== false) ? 'ok' : 'warn';
        }

        return [
            'id' => $target['id'],
            'title' => $target['title'],
            'status' => $status,
            'tunnel_ok' => $tunnelOk,
            'error' => $tunnelOk ? null : (string) ($trace['error'] ?? 'Туннель не поднялся'),
            'latency_ms' => isset($trace['total_ms']) ? (int) $trace['total_ms'] : null,
            'connect_ms' => isset($trace['connect_ms']) ? (int) $trace['connect_ms'] : null,
            'handshake_ms' => isset($trace['appconnect_ms']) ? (int) $trace['appconnect_ms'] : null,
            'egress_ip' => $egressIp,
            'egress_colo' => isset($trace['egress_colo']) ? (string) $trace['egress_colo'] : null,
            'egress_ok' => $egressOk,
            'download_mbps' => is_array($speed) ? ($speed['mbps'] ?? null) : null,
            'sites' => $sites,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, egress_ip?: string, egress_colo?: string, connect_ms?: int, appconnect_ms?: int, total_ms?: int}
     */
    private function curlThroughSocks(int $socksPort, string $url, bool $captureBody): array
    {
        if ($url === '') {
            return ['ok' => false, 'error' => 'Пустой URL проверки'];
        }

        $writeOut = '\n'.implode('\n', ['%{time_connect}', '%{time_appconnect}', '%{time_total}']);
        $result = Process::timeout(25)->run([
            'curl',
            '-fsS',
            '--connect-timeout', '12',
            '--max-time', '22',
            '--socks5-hostname', "127.0.0.1:{$socksPort}",
            '-w', $writeOut,
            $url,
        ]);

        if (! $result->successful()) {
            return [
                'ok' => false,
                'error' => trim($result->errorOutput()) ?: trim($result->output()) ?: 'curl error',
            ];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($result->output())) ?: [];
        $timing = array_map('floatval', array_slice($lines, -3));
        $bodyLines = array_slice($lines, 0, max(0, count($lines) - 3));
        $body = implode("\n", $bodyLines);

        $trace = $captureBody ? $this->parseTraceBody($body) : [];

        return [
            'ok' => true,
            'egress_ip' => $trace['ip'] ?? null,
            'egress_colo' => $trace['colo'] ?? null,
            'connect_ms' => isset($timing[0]) ? (int) round($timing[0] * 1000) : null,
            'appconnect_ms' => isset($timing[1]) ? (int) round($timing[1] * 1000) : null,
            'total_ms' => isset($timing[2]) ? (int) round($timing[2] * 1000) : null,
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
     * @return array{mbps: float|null, bytes: int|null, seconds: float|null}|null
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
            return ['mbps' => null, 'bytes' => null, 'seconds' => null];
        }

        $parts = preg_split('/\s+/', trim($result->output())) ?: [];
        if (count($parts) < 2) {
            return ['mbps' => null, 'bytes' => null, 'seconds' => null];
        }

        $bytes = (int) $parts[0];
        $seconds = (float) $parts[1];
        if ($bytes < 1 || $seconds <= 0) {
            return ['mbps' => null, 'bytes' => $bytes, 'seconds' => $seconds];
        }

        $mbps = ($bytes * 8) / ($seconds * 1_000_000);

        return [
            'mbps' => round($mbps, 1),
            'bytes' => $bytes,
            'seconds' => round($seconds, 2),
        ];
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, ms: int|null, error: string|null}>
     */
    private function probeSites(int $socksPort): array
    {
        $out = [];

        foreach ((array) config('path_probe.sites', []) as $site) {
            if (! is_array($site)) {
                continue;
            }

            $key = trim((string) ($site['key'] ?? ''));
            $label = trim((string) ($site['label'] ?? $key));
            $url = trim((string) ($site['url'] ?? ''));
            if ($key === '' || $url === '') {
                continue;
            }

            $result = Process::timeout(20)->run([
                'curl',
                '-sS',
                '--connect-timeout', '10',
                '--max-time', '18',
                '--socks5-hostname', "127.0.0.1:{$socksPort}",
                '-o', '/dev/null',
                '-w', '%{http_code} %{time_total}',
                $url,
            ]);

            $parts = preg_split('/\s+/', trim($result->output()), 2) ?: [];
            $httpCode = isset($parts[0]) ? (int) $parts[0] : 0;
            $seconds = isset($parts[1]) ? (float) $parts[1] : 0.0;
            $httpOk = $httpCode >= 200 && $httpCode < 400;
            $ok = $result->successful() && $httpOk;

            $out[] = [
                'key' => $key,
                'label' => $label !== '' ? $label : $key,
                'ok' => $ok,
                'http_code' => $httpCode > 0 ? $httpCode : null,
                'ms' => $ok ? (int) round($seconds * 1000) : null,
                'error' => $ok ? null : (trim($result->errorOutput()) ?: ($httpCode > 0 ? 'HTTP '.$httpCode : 'fail')),
            ];
        }

        return $out;
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
     * @param  list<array<string, mixed>>  $sites
     */
    private function sitesHealthy(array $sites): ?bool
    {
        if ($sites === []) {
            return null;
        }

        foreach ($sites as $site) {
            if (! ($site['ok'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{id: string, title: string, vless_uri?: string}  $target
     * @return array<string, mixed>
     */
    private function skippedNode(array $target, string $reason): array
    {
        return [
            'id' => $target['id'],
            'title' => $target['title'],
            'status' => 'skip',
            'tunnel_ok' => false,
            'error' => $reason,
            'latency_ms' => null,
            'connect_ms' => null,
            'handshake_ms' => null,
            'egress_ip' => null,
            'egress_colo' => null,
            'egress_ok' => null,
            'download_mbps' => null,
            'sites' => [],
            'duration_ms' => null,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array{id: string, title: string, vless_uri?: string}  $target
     * @return array<string, mixed>
     */
    private function failedNode(array $target, string $reason): array
    {
        $node = $this->skippedNode($target, $reason);
        $node['status'] = 'fail';

        return $node;
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
