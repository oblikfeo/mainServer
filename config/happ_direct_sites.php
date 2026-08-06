<?php

/**
 * Базовый DirectSites для Happ (без geosite/geoip .dat).
 * RU-сервисы, банки и Госуслуги — напрямую, без VPN-туннеля.
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
    // Сбер: отдельные корни (поддомены sberbank.ru уже покрыты domain:sberbank.ru)
    'domain:sberbank.ru',
    'domain:sber.ru',
    'domain:sberbank.com',
    'domain:cdnflow.ru',
    'domain:vtb.ru',
    'domain:tbank.ru',
    'domain:tinkoff.ru',
    'domain:alfabank.ru',
    'domain:raiffeisen.ru',
    'domain:gazprombank.ru',
    'domain:psbank.ru',
    'domain:open.ru',
    'domain:rshb.ru',
    'domain:rosbank.ru',
    'domain:mkb.ru',
    'domain:sovcombank.ru',
    'domain:homecredit.ru',
    'domain:rencredit.ru',
    'domain:otpbank.ru',
    'domain:modulbank.ru',
    'domain:tochka.com',
    'domain:nspk.ru',
    'domain:gosuslugi.ru',
];
