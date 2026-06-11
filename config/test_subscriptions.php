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
            'note' => 'RUVDS 195.133.198.100',
            'lines' => [
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.100:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-195.133.198.100',
            ],
        ],

        // --- tester B ---
        'M9rZ4tNpVbHkLqJ7sX2eWcFdYxAhUgRy' => [
            'title' => 'IHOR test • B',
            'note' => 'RUVDS 195.133.198.100',
            'lines' => [
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.100:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#IHOR-195.133.198.100',
            ],
        ],

        // --- shared stack QA (порядок как в боевой /sub/) ---
        'QaSharedStackBg31First9kLm2p' => [
            'title' => 'QA shared stack',
            'note' => 'BG31 → 777 → RUVDS (порядок подписки)',
            'lines' => [
                'vless://41db8d58-42a7-416d-958d-8c3f62552a50@31.22.10.250:443?type=tcp&security=reality&sni=www.microsoft.com&fp=chrome&pbk=fOVyu3dutROsm9P9mrmE_ORI8ZmTXMRM8iQ_wun3zHA&sid=a1b2c3d4&spx=%2F&flow=xtls-rprx-vision#BG31',
                'vless://8514d862-4b38-4c67-9d81-036919822285@169.40.15.141:443?type=tcp&security=reality&sni=www.microsoft.com&fp=chrome&pbk=gmRh1p7ByPBKYm4baCj9Oh7vTKbmbbssuJ7LCGHQTVg&sid=a1b2c3d4&spx=%2F&flow=xtls-rprx-vision#777',
                'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.100:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision#RUVDS',
            ],
        ],
    ],
];
