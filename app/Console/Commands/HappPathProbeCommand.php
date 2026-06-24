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
        $results = $probes->cachedResults(refresh: (bool) $this->option('refresh'));

        $this->line('checked_at='.($results['checked_at'] ?? ''));
        $this->line('xray='.(($results['xray_available'] ?? false) ? '1' : '0'));

        foreach ($results['nodes'] ?? [] as $node) {
            if (! is_array($node)) {
                continue;
            }

            $this->line(sprintf(
                '%s status=%s tunnel=%s ip=%s latency=%s mbps=%s',
                (string) ($node['id'] ?? '?'),
                (string) ($node['status'] ?? '?'),
                ($node['tunnel_ok'] ?? false) ? '1' : '0',
                (string) ($node['egress_ip'] ?? '-'),
                (string) ($node['latency_ms'] ?? '-'),
                (string) ($node['download_mbps'] ?? '-'),
            ));
        }

        return self::SUCCESS;
    }
}
