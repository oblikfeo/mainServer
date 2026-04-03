<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class BundleSshMetrics
{
    public function fetch(array $bundle): ?array
    {
        $keyPath = (string) ($bundle['ssh_private_key'] ?? '');
        $host = (string) ($bundle['ip'] ?? '');
        $user = (string) ($bundle['ssh_user'] ?? '');

        if ($keyPath === '' || ! is_readable($keyPath) || $host === '' || $user === '') {
            return null;
        }

        $script = <<<'BASH'
load1=$(awk '{print $1}' /proc/loadavg)
cpus=$(nproc)
mem=$(awk '/MemAvailable:/ {print $2}' /proc/meminfo)
iface=$(ip -4 route show default 2>/dev/null | awk '/default/ {print $5}' | head -1)
rx=0
tx=0
if [ -n "$iface" ] && [ -r "/sys/class/net/$iface/statistics/rx_bytes" ]; then
  rx=$(cat "/sys/class/net/$iface/statistics/rx_bytes")
  tx=$(cat "/sys/class/net/$iface/statistics/tx_bytes")
fi
printf 'load1:%s\ncpus:%s\nmem_avail_kb:%s\nrx_bytes:%s\ntx_bytes:%s\n' "$load1" "$cpus" "$mem" "$rx" "$tx"
BASH;

        try {
            $result = Process::timeout(18)
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
            Log::debug('bundle ssh metrics', ['bundle' => $bundle['id'] ?? '', 'error' => $e->getMessage()]);

            return null;
        }

        if (! $result->successful()) {
            Log::debug('bundle ssh metrics failed', [
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

        if (! isset($data['load1'], $data['cpus'], $data['mem_avail_kb'], $data['rx_bytes'], $data['tx_bytes'])) {
            return null;
        }

        $cpus = max(1, (int) $data['cpus']);
        $load1 = (float) $data['load1'];
        $memKb = max(0, (int) $data['mem_avail_kb']);
        $rx = max(0, (int) $data['rx_bytes']);
        $tx = max(0, (int) $data['tx_bytes']);
        $trafficTotal = $rx + $tx;

        return [
            'load1' => $load1,
            'cpus' => $cpus,
            'load_per_cpu' => round($load1 / $cpus, 2),
            'mem_avail_kb' => $memKb,
            'mem_avail_gb' => round($memKb / 1024 / 1024, 2),
            'rx_bytes' => $rx,
            'tx_bytes' => $tx,
            'traffic_total_bytes' => $trafficTotal,
        ];
    }
}
