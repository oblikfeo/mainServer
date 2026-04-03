<?php

namespace App\Services;

class BundleHealthChecker
{
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
}
