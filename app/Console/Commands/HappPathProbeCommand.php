<?php

namespace App\Console\Commands;

use App\Services\HappPathProbeService;
use Illuminate\Console\Command;

class HappPathProbeCommand extends Command
{
    protected $signature = 'happ:probe-paths {--refresh : Ignore cache and run probes now}';

    protected $description = 'Client-path health probes for shared Happ nodes (VLESS via local Xray)';

    public function handle(HappPathProbeService $probes): int
    {
        $results = $this->option('refresh')
            ? $probes->refreshAndCache()
            : $probes->forPage();

        $this->line('checked_at='.($results['checked_at'] ?? ''));
        $this->line('xray='.(($results['xray_available'] ?? false) ? '1' : '0'));

        foreach ($results['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $this->line(sprintf(
                '%s kind=%s status=%s ip=%s latency=%s mbps=%s',
                (string) ($row['id'] ?? '?'),
                (string) ($row['kind'] ?? '?'),
                (string) ($row['status'] ?? '?'),
                (string) ($row['egress_ip'] ?? '-'),
                (string) ($row['latency_ms'] ?? '-'),
                (string) ($row['download_mbps'] ?? '-'),
            ));
        }

        return self::SUCCESS;
    }
}
