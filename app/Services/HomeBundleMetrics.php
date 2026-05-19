<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Метрики домашнего VPS (доступы5): CPU/RAM + отдельно онлайн VLESS (TCP :443) и Hy2 (UDP :443).
 */
final class HomeBundleMetrics
{
    public function fetch(array $bundle): ?array
    {
        $keyPath = (string) ($bundle['ssh_private_key'] ?? '');
        $host = (string) ($bundle['ip'] ?? '');
        $user = (string) ($bundle['ssh_user'] ?? 'root');

        if ($keyPath === '' || ! is_readable($keyPath) || $host === '') {
            return null;
        }

        $port = max(1, min(65535, (int) ($bundle['client_tcp_port'] ?? 443)));

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
count_tcp() {
  ss -Hnt state established 2>/dev/null | awk -v p="$port" '
  function local_port_ok(s) { return (s ~ ":" p "$" || s ~ "]:" p "$") }
  function peer_ip(peer, ip) {
    if (peer ~ /^\[::ffff:[0-9.]+\]:[0-9]+$/) { ip=peer; sub(/^\[::ffff:/,"",ip); sub(/\]:[0-9]+$/,"",ip); return ip }
    if (peer ~ /^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+:[0-9]+$/) { ip=peer; sub(/:[0-9]+$/,"",ip); return ip }
    return ""
  }
  { if (NF<4) next; loc=$3; peer=$4; if ($1 ~ /^[0-9]+$/) { loc=$3; peer=$4 } else { loc=$5; peer=$6 }
    if (!local_port_ok(loc)) next; ip=peer_ip(peer); if (ip=="" || ip=="127.0.0.1") next; print ip }
  ' | sort -u | wc -l | tr -d " "
}
count_udp() {
  ss -Hna udp 2>/dev/null | awk -v p="$port" '
  function local_port_ok(s) { return (s ~ ":" p "$" || s ~ "]:" p "$") }
  function peer_ip(peer, ip) {
    if (peer ~ /^\[::ffff:[0-9.]+\]:[0-9]+$/) { ip=peer; sub(/^\[::ffff:/,"",ip); sub(/\]:[0-9]+$/,"",ip); return ip }
    if (peer ~ /^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+:[0-9]+$/) { ip=peer; sub(/:[0-9]+$/,"",ip); return ip }
    return ""
  }
  { loc=""; peer=""; if (NF>=4) { loc=$3; peer=$4 }
    if (loc=="" && NF>=5) { loc=$4; peer=$5 }
    if (!local_port_ok(loc)) next
    ip=peer_ip(peer); if (ip=="" || ip=="127.0.0.1" || ip=="*") next; print ip }
  ' | sort -u | wc -l | tr -d " "
}
home_vless_online=$(count_tcp)
home_hy2_online=$(count_udp)
home_vless_online=${home_vless_online:-0}
home_hy2_online=${home_hy2_online:-0}
printf 'load1:%s\ncpus:%s\nmem_total_kb:%s\nmem_avail_kb:%s\nrx_bytes:%s\ntx_bytes:%s\nconntrack_used:%s\nconntrack_max:%s\nhome_vless_online:%s\nhome_hy2_online:%s\n' \
  "$load1" "$cpus" "$mem_total" "$mem_avail" "$rx" "$tx" "$ct_used" "$ct_max" "$home_vless_online" "$home_hy2_online"
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
                    (string) $port,
                ]);
        } catch (Throwable $e) {
            Log::debug('home bundle metrics', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $result->successful()) {
            Log::debug('home bundle metrics failed', ['err' => $result->errorOutput()]);

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

        if (! isset($data['load1'], $data['cpus'], $data['mem_total_kb'], $data['home_vless_online'], $data['home_hy2_online'])) {
            return null;
        }

        $cpus = max(1, (int) $data['cpus']);
        $load1 = (float) $data['load1'];
        $memTotalKb = max(1, (int) $data['mem_total_kb']);
        $memAvailKb = max(0, (int) ($data['mem_avail_kb'] ?? 0));
        $memUsedKb = max(0, $memTotalKb - $memAvailKb);
        $rx = max(0, (int) ($data['rx_bytes'] ?? 0));
        $tx = max(0, (int) ($data['tx_bytes'] ?? 0));
        $ctUsed = (int) ($data['conntrack_used'] ?? 0);
        $ctMax = (int) ($data['conntrack_max'] ?? 0);
        $ctPct = $ctMax > 0 ? (int) min(100, max(0, round(100 * $ctUsed / $ctMax))) : null;

        $loadPerCpu = $load1 / $cpus;
        $cpuUtilPct = (int) min(100, max(0, round($loadPerCpu * 100)));
        $memUsedPct = (int) min(100, max(0, round(100 * $memUsedKb / $memTotalKb)));

        $vlessOnline = (int) $data['home_vless_online'];
        $hy2Online = (int) $data['home_hy2_online'];

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
            'traffic_total_bytes' => $rx + $tx,
            'conntrack_used' => $ctUsed,
            'conntrack_max' => $ctMax,
            'conntrack_pct' => $ctPct,
            'conntrack_level' => $ctMax > 0 && $ctPct !== null ? $this->conntrackLevel($ctPct) : null,
            'cpu_level' => $this->loadLevel($loadPerCpu),
            'ram_level' => $this->ramLevel($memUsedPct),
            'home_vless_online' => $vlessOnline,
            'home_hy2_online' => $hy2Online,
            'unique_remote_ips' => $vlessOnline + $hy2Online,
        ];
    }

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
