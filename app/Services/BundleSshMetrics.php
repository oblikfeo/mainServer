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
mem_total=$(awk '/MemTotal:/ {print $2}' /proc/meminfo)
mem_avail=$(awk '/MemAvailable:/ {print $2}' /proc/meminfo)
iface=$(ip -4 route show default 2>/dev/null | awk '/default/ {print $5}' | head -1)
rx=0
tx=0
if [ -n "$iface" ] && [ -r "/sys/class/net/$iface/statistics/rx_bytes" ]; then
  rx=$(cat "/sys/class/net/$iface/statistics/rx_bytes")
  tx=$(cat "/sys/class/net/$iface/statistics/tx_bytes")
fi
printf 'load1:%s\ncpus:%s\nmem_total_kb:%s\nmem_avail_kb:%s\nrx_bytes:%s\ntx_bytes:%s\n' "$load1" "$cpus" "$mem_total" "$mem_avail" "$rx" "$tx"
BASH;

        try {
            $result = Process::path(base_path())
                ->timeout(18)
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

        if (! isset($data['load1'], $data['cpus'], $data['mem_total_kb'], $data['mem_avail_kb'], $data['rx_bytes'], $data['tx_bytes'])) {
            return null;
        }

        $cpus = max(1, (int) $data['cpus']);
        $load1 = (float) $data['load1'];
        $memTotalKb = max(1, (int) $data['mem_total_kb']);
        $memAvailKb = max(0, (int) $data['mem_avail_kb']);
        $memUsedKb = max(0, $memTotalKb - $memAvailKb);
        $rx = max(0, (int) $data['rx_bytes']);
        $tx = max(0, (int) $data['tx_bytes']);
        $trafficTotal = $rx + $tx;

        $loadPerCpu = $load1 / $cpus;
        $cpuUtilPct = (int) min(100, max(0, round($loadPerCpu * 100)));
        $memUsedPct = (int) min(100, max(0, round(100 * $memUsedKb / $memTotalKb)));

        return [
            'load1' => $load1,
            'cpus' => $cpus,
            'load_per_cpu' => round($loadPerCpu, 2),
            'cpu_util_pct' => $cpuUtilPct,
            'mem_total_kb' => $memTotalKb,
            'mem_avail_kb' => $memAvailKb,
            'mem_used_kb' => $memUsedKb,
            'mem_total_gb' => round($memTotalKb / 1024 / 1024, 2),
            'mem_used_gb' => round($memUsedKb / 1024 / 1024, 2),
            'mem_avail_gb' => round($memAvailKb / 1024 / 1024, 2),
            'mem_used_pct' => $memUsedPct,
            'rx_bytes' => $rx,
            'tx_bytes' => $tx,
            'traffic_total_bytes' => $trafficTotal,
            'cpu_level' => $this->loadLevel($loadPerCpu),
            'ram_level' => $this->ramLevel($memUsedPct),
        ];
    }

    /**
     * Load average за 1 мин на одно ядро: ниже 0,65 — ок, до ~1,15 — жёлтый, выше — красный.
     */
    private function loadLevel(float $loadPerCpu): string
    {
        if ($loadPerCpu < 0.65) {
            return 'ok';
        }
        if ($loadPerCpu < 1.15) {
            return 'warn';
        }

        return 'crit';
    }

    /**
     * Доля занятой RAM (оценка по MemTotal − MemAvailable).
     */
    private function ramLevel(int $memUsedPct): string
    {
        if ($memUsedPct < 70) {
            return 'ok';
        }
        if ($memUsedPct < 88) {
            return 'warn';
        }

        return 'crit';
    }
}
