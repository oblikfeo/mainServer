<?php

/**
 * DirectSites для Happ (без geosite/geoip .dat).
 *
 * На LTE с whitelist direct = пакет не доходит до IP (банки, многие RU-сервисы).
 * Банки идут через VPN (GlobalProxy) → CDN → egress → интернет.
 *
 * Direct: push, маркетплейсы, соцсети — то, что реально доступно с телефона напрямую.
 *
 * @return list<string>
 */
return [
    'domain:mtalk.google.com',
    'domain:push.apple.com',
    'domain:api.push.apple.com',
    'domain:push-apple.com.akadns.net',
    'domain:courier.push.apple.com',
    'domain:ozon.ru',
    'domain:wildberries.ru',
    'domain:wbbasket.ru',
    'domain:wb.ru',
    'domain:vk.com',
    'domain:mail.ru',
    'domain:yandex.ru',
    'domain:yandex.net',
    'domain:yandex.com',
    'domain:vkusvill.ru',
    'domain:avito.ru',
    'domain:2gis.ru',
    'domain:2gis.com',
    'domain:2ip.ru',
];
