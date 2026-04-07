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

        $clientPort = max(1, min(65535, (int) ($bundle['client_tcp_port'] ?? 443)));

        $script = <<<'BASH'
port="${1:-443}"
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
ct_used=0
ct_max=0
if [ -r /proc/sys/net/netfilter/nf_conntrack_count ]; then
  ct_used=$(cat /proc/sys/net/netfilter/nf_conntrack_count)
fi
if [ -r /proc/sys/net/netfilter/nf_conntrack_max ]; then
  ct_max=$(cat /proc/sys/net/netfilter/nf_conntrack_max)
fi
unique_remote_ips=0
if command -v ss >/dev/null 2>&1; then
  # Входящие на этот сервер: локальный порт = $port (колонка Local), не sport в фильтре ss.
  unique_remote_ips=$(ss -Hnt state established 2>/dev/null | awk -v p="$port" '
  {
    loc = $4
    peer = $5
    if (!(loc ~ ":" p "$" || loc ~ "]:" p "$")) next
    if (peer ~ /^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+:[0-9]+$/) {
      sub(/:[0-9]+$/, "", peer)
    } else if (peer ~ /^\[[^]]+\]:[0-9]+$/) {
      sub(/^\[/, "", peer)
      sub(/\]:[0-9]+$/, "", peer)
    } else next
    if (peer == "" || peer == "*" || peer == "127.0.0.1" || peer == "::1") next
    print peer
  }' | sort -u | wc -l | tr -d " ")
fi
printf 'load1:%s\ncpus:%s\nmem_total_kb:%s\nmem_avail_kb:%s\nrx_bytes:%s\ntx_bytes:%s\nconntrack_used:%s\nconntrack_max:%s\nunique_remote_ips:%s\n' "$load1" "$cpus" "$mem_total" "$mem_avail" "$rx" "$tx" "$ct_used" "$ct_max" "$unique_remote_ips"
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
                    '--',
                    (string) $clientPort,
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

        $ctUsed = (int) ($data['conntrack_used'] ?? 0);
        $ctMax = (int) ($data['conntrack_max'] ?? 0);
        $ctPct = $ctMax > 0 ? (int) min(100, max(0, round(100 * $ctUsed / $ctMax))) : null;

        $loadPerCpu = $load1 / $cpus;
        $cpuUtilPct = (int) min(100, max(0, round($loadPerCpu * 100)));
        $memUsedPct = (int) min(100, max(0, round(100 * $memUsedKb / $memTotalKb)));

        $uniqueIps = (int) ($data['unique_remote_ips'] ?? 0);

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
            'conntrack_used' => $ctUsed,
            'conntrack_max' => $ctMax,
            'conntrack_pct' => $ctPct,
            'conntrack_level' => $ctMax > 0 && $ctPct !== null ? $this->conntrackLevel($ctPct) : null,
            'cpu_level' => $this->loadLevel($loadPerCpu),
            'ram_level' => $this->ramLevel($memUsedPct),
            'unique_remote_ips' => $uniqueIps,
        ];
    }

    /** Заполнение таблицы conntrack (NAT под нагрузкой клиентов). */
    private function conntrackLevel(int $pct): string
    {
        if ($pct < 75) {
            return 'ok';
        }
        if ($pct < 90) {
            return 'warn';
        }

        return 'crit';
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
