<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class BundleHealthChecker
{
    /**
     * Составная проверка связки:
     * — с хаба: TCP до клиентского порта (обычно 443);
     * — по SSH (если ключ настроен): маршрут наружу, LISTEN :443, процесс Xray, исходящий HTTPS.
     *
     * @return array{
     *     online: bool,
     *     tcp_client: bool,
     *     ssh_ok: bool|null,
     *     default_route: bool|null,
     *     listen_443: bool|null,
     *     xray: bool|null,
     *     egress_https: bool|null,
     * }
     */
    public function evaluateBundle(array $bundle): array
    {
        $host = (string) ($bundle['ip'] ?? '');
        $clientPort = (int) ($bundle['client_tcp_port'] ?? config('links.health.client_tcp_port', 443));

        $base = [
            'online' => false,
            'tcp_client' => false,
            'ssh_ok' => null,
            'default_route' => null,
            'listen_443' => null,
            'xray' => null,
            'egress_https' => null,
        ];

        if ($host === '' || $clientPort < 1 || $clientPort > 65535) {
            return $base;
        }

        $tcpOk = $this->tcpReachable($host, $clientPort);
        $base['tcp_client'] = $tcpOk;

        $keyPath = (string) ($bundle['ssh_private_key'] ?? '');
        $user = (string) ($bundle['ssh_user'] ?? '');
        $hasSsh = $keyPath !== '' && is_readable($keyPath) && $user !== '';

        if (! $hasSsh) {
            $base['online'] = $tcpOk;

            return $base;
        }

        $remote = $this->fetchRemoteHealthFlags($bundle);
        if ($remote === null) {
            $base['ssh_ok'] = false;
            $base['online'] = false;

            return $base;
        }

        $base['ssh_ok'] = true;
        $base['default_route'] = $remote['default_route'];
        $base['listen_443'] = $remote['listen_443'];
        $base['xray'] = $remote['xray'];
        $base['egress_https'] = $remote['egress_https'];

        $remoteOk = $remote['default_route']
            && $remote['listen_443']
            && $remote['xray']
            && $remote['egress_https'];

        $base['online'] = $tcpOk && $remoteOk;

        return $base;
    }

    public function tcpReachable(string $host, int $port, ?float $timeoutSeconds = null): bool
    {
        $timeout = $timeoutSeconds ?? (float) config('links.tcp_timeout_seconds', 2);
        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout
        );

        if (! is_resource($socket)) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /**
     * @return array{default_route: bool, listen_443: bool, xray: bool, egress_https: bool}|null
     */
    private function fetchRemoteHealthFlags(array $bundle): ?array
    {
        $keyPath = (string) ($bundle['ssh_private_key'] ?? '');
        $host = (string) ($bundle['ip'] ?? '');
        $user = (string) ($bundle['ssh_user'] ?? '');

        $script = <<<'BASH'
default_route=0
if ip route get 1.1.1.1 >/dev/null 2>&1; then default_route=1; fi
listen_443=0
if command -v ss >/dev/null 2>&1; then
  if ss -tln 2>/dev/null | grep -qE ':443[[:space:]]'; then listen_443=1; fi
fi
xray=0
if systemctl is-active --quiet xray 2>/dev/null; then xray=1; fi
if [ "$xray" = 0 ] && systemctl is-active --quiet x-ui 2>/dev/null; then xray=1; fi
if [ "$xray" = 0 ] && pgrep -x xray >/dev/null 2>&1; then xray=1; fi
if [ "$xray" = 0 ] && pgrep -f '[x]ray-linux' >/dev/null 2>&1; then xray=1; fi
if [ "$xray" = 0 ] && pgrep -f '/xray' >/dev/null 2>&1; then xray=1; fi
if [ "$xray" = 0 ] && pgrep -x x-ui >/dev/null 2>&1; then xray=1; fi
egress_https=0
if command -v curl >/dev/null 2>&1; then
  if curl -fsS --connect-timeout 4 --max-time 12 "https://www.cloudflare.com/cdn-cgi/trace" -o /dev/null 2>/dev/null; then egress_https=1; fi
fi
printf 'default_route:%s\nlisten_443:%s\nxray:%s\negress_https:%s\n' "$default_route" "$listen_443" "$xray" "$egress_https"
BASH;

        try {
            $timeout = (int) config('links.health.ssh_timeout_seconds', 22);
            $result = Process::path(base_path())
                ->timeout($timeout)
                ->input($script)
                ->run([
                    'ssh',
                    '-i', $keyPath,
                    '-o', 'BatchMode=yes',
                    '-o', 'StrictHostKeyChecking=no',
                    '-o', 'UserKnownHostsFile=/dev/null',
                    '-o', 'ConnectTimeout=10',
                    "{$user}@{$host}",
                    'bash', '-s',
                ]);
        } catch (Throwable $e) {
            Log::debug('bundle health ssh', ['bundle' => $bundle['id'] ?? '', 'error' => $e->getMessage()]);

            return null;
        }

        if (! $result->successful()) {
            Log::debug('bundle health ssh failed', [
                'bundle' => $bundle['id'] ?? '',
                'err' => $result->errorOutput(),
            ]);

            return null;
        }

        $data = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($result->output())) as $line) {
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $data[trim($k)] = trim($v);
        }

        foreach (['default_route', 'listen_443', 'xray', 'egress_https'] as $key) {
            if (! isset($data[$key])) {
                return null;
            }
        }

        return [
            'default_route' => $data['default_route'] === '1',
            'listen_443' => $data['listen_443'] === '1',
            'xray' => $data['xray'] === '1',
            'egress_https' => $data['egress_https'] === '1',
        ];
    }
}
