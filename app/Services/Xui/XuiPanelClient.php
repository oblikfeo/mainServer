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
}
