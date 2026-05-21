<?php

/*
 * Tiny, isolated test-subscription feeds for ad-hoc validation by specific people.
 *
 * Endpoint: GET /test-sub/{token}  -> plain Happ feed (URI list, no HWID, no panels).
 *
 * Not connected to the production subscription pipeline (no DB, no 3x-ui, no quotas).
 * Edit this file, redeploy. Empty `lines` (or unknown token) => 404.
 */

return [
    'profile_update_hours' => 6,
    'output_b64' => false,

    'tokens' => [
        // --- tester A ---
        'KZ4nW8pL3vR2sQxYmB7gT5eFcXaH9jUd' => [
            'title' => 'IHOR test • A',
            'note' => '6 IP, SibTelCo Novosibirsk',
            'lines' => [
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.100:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-01-195.133.198.100',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.190:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-02-195.133.198.190',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.205:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-03-195.133.198.205',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@194.87.213.140:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-04-194.87.213.140',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@194.87.213.141:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-05-194.87.213.141',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@194.87.213.158:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-06-194.87.213.158',
            ],
        ],

        // --- tester B ---
        'M9rZ4tNpVbHkLqJ7sX2eWcFdYxAhUgRy' => [
            'title' => 'IHOR test • B',
            'note' => '6 IP, SibTelCo Novosibirsk',
            'lines' => [
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.100:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-01-195.133.198.100',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.190:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-02-195.133.198.190',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.205:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-03-195.133.198.205',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@194.87.213.140:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-04-194.87.213.140',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@194.87.213.141:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-05-194.87.213.141',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@194.87.213.158:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-06-194.87.213.158',
            ],
        ],
    ],
];
