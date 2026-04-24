<?php

namespace App\Services\Hy2;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

final class BlitzListUsers
{
    public function totalOnlineCount(): int
    {
        $keyPath = (string) config('hy2.ssh_key', '');
        $host = (string) config('hy2.ssh_host', '');
        $user = (string) config('hy2.ssh_user', 'root');
        $cliPath = (string) config('hy2.cli_path', '/etc/hysteria/core/cli.py');
        $venvActivate = (string) config('hy2.venv_activate', '/etc/hysteria/hysteria2_venv/bin/activate');

        if ($keyPath === '' || ! is_readable($keyPath) || $host === '') {
            return 0;
        }

        $remoteCmd = "source ".escapeshellarg($venvActivate)." && cd /etc/hysteria && python3 ".escapeshellarg($cliPath).' list-users 2>/dev/null';

        try {
            $result = Process::path(base_path())
                ->timeout(25)
                ->run([
                    'ssh',
                    '-i', $keyPath,
                    '-o', 'BatchMode=yes',
                    '-o', 'StrictHostKeyChecking=no',
                    '-o', 'UserKnownHostsFile=/dev/null',
                    '-o', 'ConnectTimeout=10',
                    "{$user}@{$host}",
                    $remoteCmd,
                ]);
        } catch (Throwable $e) {
            Log::debug('hy2.blitz_list_users.ssh', ['error' => $e->getMessage()]);

            return 0;
        }

        if (! $result->successful()) {
            return 0;
        }

        $raw = trim($result->output());
        if ($raw === '' || $raw[0] !== '[') {
            return 0;
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return 0;
        }

        $sum = 0;
        foreach ($data as $row) {
            if (is_array($row)) {
                $sum += (int) ($row['online_count'] ?? 0);
            }
        }

        return $sum;
    }
}
