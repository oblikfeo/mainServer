<?php

namespace App\Services\Xui;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

final class XuiPanelClient
{
    private Client $http;

    public function __construct(string $panelBaseUrl)
    {
        $base = rtrim($panelBaseUrl, '/').'/';
        $this->http = new Client([
            'base_uri' => $base,
            'verify' => false,
            'timeout' => 120,
            'cookies' => new CookieJar,
        ]);
    }

    public function login(string $username, string $password): void
    {
        $r = $this->http->post('login', [
            'form_params' => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        $j = json_decode((string) $r->getBody(), true);
        if (empty($j['success'])) {
            throw new XuiPanelException('Вход в панель: '.($j['msg'] ?? 'неизвестная ошибка'));
        }
    }

    /**
     * @param  array<string, mixed>  $clientDef  один элемент clients[] для 3x-ui
     */
    public function addInboundClient(int $inboundId, array $clientDef): void
    {
        $settings = ['clients' => [$clientDef]];
        $r = $this->http->post('panel/api/inbounds/addClient', [
            'json' => [
                'id' => $inboundId,
                'settings' => json_encode($settings, JSON_UNESCAPED_SLASHES),
            ],
        ]);

        $j = json_decode((string) $r->getBody(), true);
        if (empty($j['success'])) {
            throw new XuiPanelException($j['msg'] ?? 'addClient отклонён');
        }
    }

    public function restartXray(): void
    {
        $r = $this->http->post('panel/api/server/restartXrayService');
        $j = json_decode((string) $r->getBody(), true);
        if (empty($j['success'])) {
            throw new XuiPanelException($j['msg'] ?? 'restartXrayService отклонён');
        }
    }

    public function deleteInboundClientByEmail(int $inboundId, string $email): void
    {
        $path = 'panel/api/inbounds/'.$inboundId.'/delClientByEmail/'.rawurlencode($email);
        $r = $this->http->post($path);
        $j = json_decode((string) $r->getBody(), true);
        if (! is_array($j) || empty($j['success'])) {
            throw new XuiPanelException($j['msg'] ?? 'delClientByEmail отклонён');
        }
    }

    /**
     * Список inbound (3x-ui: GET panel/api/inbounds/list). Нужен предварительный login().
     *
     * @return list<array<string, mixed>>
     */
    public function getInboundsList(): array
    {
        $r = $this->http->get('panel/api/inbounds/list');
        $j = json_decode((string) $r->getBody(), true);
        if (! is_array($j) || empty($j['success'])) {
            throw new XuiPanelException($j['msg'] ?? 'inbounds/list: ответ панели некорректен');
        }

        $obj = $j['obj'] ?? null;
        if (! is_array($obj)) {
            return [];
        }

        return $obj;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInboundById(int $inboundId): array
    {
        $r = $this->http->get('panel/api/inbounds/get/'.$inboundId);
        $j = json_decode((string) $r->getBody(), true);
        if (! is_array($j) || empty($j['success'])) {
            throw new XuiPanelException($j['msg'] ?? 'inbounds/get: ответ панели некорректен');
        }
        $obj = $j['obj'] ?? null;

        return is_array($obj) ? $obj : [];
    }

    /**
     * @return list<string>
     */
    public function getOnlineClientEmails(): array
    {
        $r = $this->http->post('panel/api/inbounds/onlines');
        $j = json_decode((string) $r->getBody(), true);
        if (! is_array($j) || empty($j['success'])) {
            throw new XuiPanelException($j['msg'] ?? 'inbounds/onlines: ответ панели некорректен');
        }
        $obj = $j['obj'] ?? [];
        if (! is_array($obj)) {
            return [];
        }

        $out = [];
        foreach ($obj as $email) {
            if (is_string($email) && $email !== '') {
                $out[] = $email;
            }
        }

        return $out;
    }

    /**
     * Нормализованные уникальные IP из записи клиента в панели.
     *
     * @return list<string>
     */
    public function getClientIpsNormalized(string $email): array
    {
        $r = $this->http->post('panel/api/inbounds/clientIps/'.rawurlencode($email));
        $j = json_decode((string) $r->getBody(), true);
        if (! is_array($j) || empty($j['success'])) {
            return [];
        }
        $obj = $j['obj'] ?? null;
        if ($obj === null || $obj === 'No IP Record') {
            return [];
        }
        if (! is_array($obj)) {
            return [];
        }

        $ips = [];
        foreach ($obj as $item) {
            if (! is_string($item) || $item === '') {
                continue;
            }
            $head = trim(explode(' ', $item, 2)[0]);
            if (filter_var($head, FILTER_VALIDATE_IP)) {
                $ips[$head] = true;
            }
        }

        return array_keys($ips);
    }

    /**
     * @param  array<string, mixed>  $clientRow  один элемент clients[] (полная строка из настроек inbound)
     */
    public function updateInboundClient(int $inboundId, string $clientUuid, array $clientRow): void
    {
        $settings = ['clients' => [$clientRow]];
        $r = $this->http->post('panel/api/inbounds/updateClient/'.rawurlencode($clientUuid), [
            'json' => [
                'id' => $inboundId,
                'settings' => json_encode($settings, JSON_UNESCAPED_SLASHES),
            ],
        ]);
        $j = json_decode((string) $r->getBody(), true);
        if (! is_array($j) || empty($j['success'])) {
            throw new XuiPanelException($j['msg'] ?? 'updateClient отклонён');
        }
    }
}
