<?php
declare(strict_types=1);
/**
 * Диагностика с FI-ноды (Yandex VM). Не принимает произвольные URL/хосты от клиента.
 * Секрет подставляет install-on-fi-diag.sh → hash_equals.
 */
const DIAG_SECRET = '__REPLACE_TOKEN__';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method'], JSON_UNESCAPED_UNICODE);
    exit;
}

$key = isset($_GET['k']) ? (string) $_GET['k'] : '';
if ($key === '' || !hash_equals(DIAG_SECRET, $key)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$wantHtml = isset($_GET['html']) && $_GET['html'] === '1';

/** @var array<string, mixed> $payload */
$payload = [
    'ok' => true,
    'node' => 'fi-yandex',
    'php' => PHP_VERSION,
    'hostname' => gethostname() ?: null,
    'time_utc' => gmdate('c'),
    'tests' => [],
];

// Жёсткий список целей (не из запроса пользователя).
$tcpChecks = [
    ['label' => 'self_public_443', 'host' => '158.160.241.36', 'port' => 443],
    ['label' => 'loopback_443', 'host' => '127.0.0.1', 'port' => 443],
    ['label' => 'hub_https', 'host' => 'www.nadezhda.space', 'port' => 443],
];

$dnsHosts = ['www.nadezhda.space', 'www.yahoo.com'];

$httpsUrls = [
    ['label' => 'hub_home', 'url' => 'https://www.nadezhda.space/', 'timeout' => 8],
    ['label' => 'reality_sni_probe', 'url' => 'https://www.yahoo.com/', 'timeout' => 8],
];

/**
 * @return array{ok: bool, ms: float, errno?: int, error?: string}
 */
function tcp_probe(string $host, int $port, float $timeoutSec = 2.5): array
{
    $errno = 0;
    $errstr = '';
    $t0 = microtime(true);
    $fp = @stream_socket_client(
        "tcp://{$host}:{$port}",
        $errno,
        $errstr,
        $timeoutSec,
        STREAM_CLIENT_CONNECT,
        stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,
            ],
        ])
    );
    $ms = round((microtime(true) - $t0) * 1000, 2);
    if (is_resource($fp)) {
        fclose($fp);

        return ['ok' => true, 'ms' => $ms];
    }

    return ['ok' => false, 'ms' => $ms, 'errno' => $errno, 'error' => $errstr];
}

/**
 * @return array{ok: bool, ms: float, status?: int, error?: string, bytes?: int}
 */
function https_headish(string $url, int $timeoutSec): array
{
    $t0 = microtime(true);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
            'method' => 'GET',
            'header' => "Accept: */*\r\nUser-Agent: nadezhda-fi-diag/1\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $ms = round((microtime(true) - $t0) * 1000, 2);
    if ($body === false) {
        $err = error_get_last();

        return [
            'ok' => false,
            'ms' => $ms,
            'error' => isset($err['message']) ? (string) $err['message'] : 'request_failed',
        ];
    }
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('#\b(\d{3})\b#', (string) $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }

    return [
        'ok' => $status > 0 && $status < 600,
        'ms' => $ms,
        'status' => $status,
        'bytes' => strlen($body),
    ];
}

foreach ($dnsHosts as $h) {
    $v4 = @gethostbynamel($h);
    $payload['tests']['dns_'.$h] = [
        'type' => 'dns_a',
        'host' => $h,
        'ipv4' => is_array($v4) ? $v4 : null,
    ];
}

foreach ($tcpChecks as $row) {
    $payload['tests']['tcp_'.$row['label']] = array_merge(
        ['type' => 'tcp', 'host' => $row['host'], 'port' => $row['port']],
        tcp_probe($row['host'], $row['port'])
    );
}

foreach ($httpsUrls as $row) {
    $payload['tests']['https_'.$row['label']] = array_merge(
        ['type' => 'https', 'url' => $row['url']],
        https_headish($row['url'], $row['timeout'])
    );
}

if ($wantHtml) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>FI diag</title></head><body><pre>';
    echo htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '</pre></body></html>';
    exit;
}

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n";
