<?php

namespace App\Services\Hy2;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * SSH-обёртка над Blitz CLI для управления пользователями Hysteria2.
 *
 * Все операции: ssh → `source venv/bin/activate && python3 cli.py <command>`.
 * Формат URI конструируется из config('hy2.*') + хранимых username/password.
 */
final class BlitzClient
{
    /**
     * @throws BlitzException
     */
    public function addUser(string $username, string $password, int $trafficGb, int $days): void
    {
        $this->runCli(
            'add-user',
            ['-u', $username, '-t', (string) $trafficGb, '-e', (string) $days, '-p', $password],
            "add user {$username}"
        );
    }

    /**
     * @throws BlitzException
     */
    public function removeUser(string $username): void
    {
        $this->runCli(
            'remove-user',
            [$username],
            "remove user {$username}"
        );
    }

    /**
     * @throws BlitzException
     */
    public function editUser(string $username, ?int $trafficGb = null, ?int $expirationDays = null): void
    {
        $args = ['-u', $username];
        if ($trafficGb !== null) {
            $args[] = '-nt';
            $args[] = (string) $trafficGb;
        }
        if ($expirationDays !== null) {
            $args[] = '-ne';
            $args[] = (string) $expirationDays;
        }

        $this->runCli('edit-user', $args, "edit user {$username}");
    }

    /**
     * Собирает hy2:// URI из config + username/password подписки.
     */
    public static function buildUri(string $username, string $password, ?string $displayName = null): string
    {
        $host = (string) config('hy2.host', '');
        $port = (int) config('hy2.port', 443);

        $auth = rawurlencode($username).':'.rawurlencode($password);
        $uri = "hy2://{$auth}@{$host}:{$port}";

        $params = [];

        $obfsType = (string) config('hy2.obfs_type', '');
        $obfsPass = (string) config('hy2.obfs_password', '');
        if ($obfsType !== '' && $obfsPass !== '') {
            $params['obfs'] = $obfsType;
            $params['obfs-password'] = $obfsPass;
        }

        $pin = (string) config('hy2.pin_sha256', '');
        if ($pin !== '') {
            $params['pinSHA256'] = $pin;
        }

        if (config('hy2.insecure', true)) {
            $params['insecure'] = '1';
        }

        if ($params !== []) {
            $uri .= '?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        $name = $displayName ?? (string) config('hy2.display_name', 'Hysteria2');
        $serverDesc = trim((string) config('hy2.server_description', ''));
        $sdFormat = strtolower(trim((string) config('xui.vless_server_description_format', 'b64')));

        if ($serverDesc !== '' && $sdFormat === 'b64') {
            $name .= '?serverDescription='.rtrim(base64_encode($serverDesc), '=');
        } elseif ($serverDesc !== '' && $sdFormat === 'dual') {
            $name .= '?'.$serverDesc;
        }

        $uri .= '#'.rawurlencode($name);

        return $uri;
    }

    // ------------------------------------------------------------------

    /**
     * @param  list<string>  $args
     *
     * @throws BlitzException
     */
    private function runCli(string $command, array $args, string $context): void
    {
        $keyPath = (string) config('hy2.ssh_key', '');
        $host = (string) config('hy2.ssh_host', '');
        $user = (string) config('hy2.ssh_user', 'root');
        $cliPath = (string) config('hy2.cli_path', '/etc/hysteria/core/cli.py');
        $venvActivate = (string) config('hy2.venv_activate', '/etc/hysteria/hysteria2_venv/bin/activate');

        if ($keyPath === '' || ! is_readable($keyPath)) {
            throw new BlitzException("SSH-ключ для hy2 не найден или не читаем: {$keyPath}");
        }
        if ($host === '') {
            throw new BlitzException('HY2_SSH_HOST не задан');
        }

        $venvPython = dirname($venvActivate).'/python3';
        $escapedArgs = implode(' ', array_map('escapeshellarg', $args));
        $remoteCmd = "cd /etc/hysteria && {$venvPython} {$cliPath} {$command} {$escapedArgs}";

        try {
            $result = Process::path(base_path())
                ->timeout(30)
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
        } catch (\Throwable $e) {
            Log::warning('hy2.blitz.ssh_error', ['context' => $context, 'error' => $e->getMessage()]);
            throw new BlitzException("SSH-ошибка ({$context}): ".$e->getMessage(), previous: $e);
        }

        $stdout = trim($result->output());
        $stderr = trim($result->errorOutput());

        if (! $result->successful()) {
            $msg = $stderr !== '' ? $stderr : $stdout;
            Log::warning('hy2.blitz.cli_error', [
                'context' => $context,
                'exit' => $result->exitCode(),
                'stdout' => $stdout,
                'stderr' => $stderr,
            ]);
            throw new BlitzException("Blitz CLI ({$context}): {$msg}");
        }

        if (str_contains(strtolower($stdout), 'error')) {
            Log::warning('hy2.blitz.cli_output_error', ['context' => $context, 'stdout' => $stdout]);
        }
    }
}
