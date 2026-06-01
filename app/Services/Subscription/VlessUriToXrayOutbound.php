<?php

namespace App\Services\Subscription;

/**
 * Разбор уже обработанной vless:// строки (хост/sid/tls — как в подписке) в outbound Xray.
 */
final class VlessUriToXrayOutbound
{
    /**
     * @return array<string, mixed>|null
     */
    public static function convert(string $vlessUrl, string $tag): ?array
    {
        $vlessUrl = trim($vlessUrl);
        if ($vlessUrl === '' || ! str_starts_with($vlessUrl, 'vless://')) {
            return null;
        }

        $withoutFragment = explode('#', $vlessUrl, 2)[0];

        $parts = parse_url($withoutFragment);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'vless') {
            return null;
        }

        $uuid = trim((string) ($parts['user'] ?? ''));
        $address = trim((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;

        if ($uuid === '' || $address === '' || $port < 1) {
            return null;
        }

        $query = isset($parts['query']) ? (string) $parts['query'] : '';
        $q = [];
        parse_str(str_replace('+', '%2B', $query), $q);

        $encryption = (string) ($q['encryption'] ?? 'none');
        $flow = trim((string) ($q['flow'] ?? ''));
        $netType = strtolower(trim((string) ($q['type'] ?? 'tcp')));

        $user = [
            'id' => $uuid,
            'encryption' => $encryption !== '' ? $encryption : 'none',
        ];
        if ($flow !== '') {
            $user['flow'] = $flow;
        }

        $stream = self::buildStreamSettings($q, $netType);
        if ($stream === null) {
            return null;
        }

        $requestedSec = strtolower(trim((string) ($q['security'] ?? '')));
        if ($requestedSec === 'reality' && (($stream['security'] ?? '') !== 'reality')) {
            return null;
        }

        return [
            'protocol' => 'vless',
            'tag' => $tag,
            'settings' => [
                'vnext' => [
                    [
                        'address' => $address,
                        'port' => $port,
                        'users' => [$user],
                    ],
                ],
            ],
            'streamSettings' => $stream,
        ];
    }

    /**
     * @param  array<string, scalar>  $q
     * @return array<string, mixed>|null
     */
    private static function buildStreamSettings(array $q, string $netType): ?array
    {
        $security = strtolower(trim((string) ($q['security'] ?? '')));

        if ($security === '' && $netType === 'grpc') {
            $security = 'tls';
        }

        return match ($netType) {
            'tcp' => self::streamTcp($q, $security),
            'ws', 'websocket' => self::streamWs($q, $security),
            'grpc' => self::streamGrpc($q, $security),
            default => self::streamTcp($q, $security),
        };
    }

    /**
     * @param  array<string, scalar>  $q
     * @return array<string, mixed>|null
     */
    private static function streamTcp(array $q, string $security): ?array
    {
        $out = [
            'network' => 'tcp',
            'tcpSettings' => self::truthy((string) ($q['headerType'] ?? '')) === 'http'
                ? [
                    'header' => [
                        'type' => 'http',
                        'request' => ['version' => '1.1', 'headers' => (object) []],
                        'response' => ['version' => '1.1', 'reason' => 'OK', 'headers' => (object) []],
                    ],
                ]
                : new \stdClass,
        ];

        self::attachSecurity($out, $q, $security);

        return $out;
    }

    /**
     * @param  array<string, scalar>  $q
     * @return array<string, mixed>|null
     */
    private static function streamWs(array $q, string $security): ?array
    {
        $path = trim((string) ($q['path'] ?? '/'));
        if ($path === '') {
            $path = '/';
        }

        $hostHeader = trim((string) ($q['host'] ?? ''));

        $headers = [];
        if ($hostHeader !== '') {
            $headers['Host'] = $hostHeader;
        }

        $out = [
            'network' => 'ws',
            'wsSettings' => [
                'path' => $path,
                'headers' => $headers !== [] ? $headers : new \stdClass,
            ],
        ];

        self::attachSecurity($out, $q, $security !== '' ? $security : 'tls');

        return $out;
    }

    /**
     * @param  array<string, scalar>  $q
     * @return array<string, mixed>|null
     */
    private static function streamGrpc(array $q, string $security): ?array
    {
        $serviceName = trim((string) ($q['serviceName'] ?? ''));

        $authority = trim((string) ($q['authority'] ?? ''));

        $grpc = [
            'serviceName' => $serviceName,
            'multiMode' => isset($q['multiMode'])
                ? filter_var((string) $q['multiMode'], FILTER_VALIDATE_BOOL)
                : false,
        ];
        if ($authority !== '') {
            $grpc['authority'] = $authority;
        }

        $out = [
            'network' => 'grpc',
            'grpcSettings' => $grpc,
        ];

        self::attachSecurity($out, $q, $security !== '' ? $security : 'tls');

        return $out;
    }

    /**
     * @param  array<string, mixed>  $out  modified in place
     * @param  array<string, scalar>  $q
     */
    private static function attachSecurity(array &$out, array $q, string $security): void
    {
        $security = strtolower($security);

        if ($security === 'reality') {
            $spx = (string) ($q['spx'] ?? '/');
            if ($spx === '') {
                $spx = '/';
            }

            $pbk = trim((string) ($q['pbk'] ?? ''));
            $sni = trim((string) ($q['sni'] ?? ''));
            $sid = trim((string) ($q['sid'] ?? ''));
            $fp = trim((string) ($q['fp'] ?? 'chrome'));

            if ($pbk === '' || $sni === '') {
                return;
            }

            $out['security'] = 'reality';
            $out['realitySettings'] = [
                'fingerprint' => $fp !== '' ? $fp : 'chrome',
                'publicKey' => $pbk,
                'serverName' => $sni,
                'shortId' => $sid,
                'spiderX' => $spx,
            ];

            return;
        }

        if ($security === 'tls' || $security === '') {
            $out['security'] = 'tls';

            $sni = trim((string) ($q['sni'] ?? ''));
            $pinSha256 = trim((string) ($q['pinSHA256'] ?? $q['pcs'] ?? ''));
            $verifyName = trim((string) ($q['vcn'] ?? ''));

            $tlsSettings = [];

            if ($sni !== '') {
                $tlsSettings['serverName'] = $sni;
            }

            if ($pinSha256 !== '') {
                $tlsSettings['pinnedPeerCertSha256'] = $pinSha256;
            }

            if ($verifyName !== '') {
                $tlsSettings['verifyPeerCertByName'] = $verifyName;
            }

            $out['tlsSettings'] = $tlsSettings !== [] ? $tlsSettings : new \stdClass;

            return;
        }

        if ($security === 'none') {
            $out['security'] = 'none';

            return;
        }

        $out['security'] = $security;
    }

    private static function truthy(string $v): bool
    {
        $v = strtolower(trim($v));

        return $v !== '' && $v !== '0' && $v !== 'false' && $v !== 'no';
    }
}
