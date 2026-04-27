<?php

/**
 * Рассылка «вход в кабинет»: логин = email, пароль — через восстановление.
 * mass-invite:test-mail — одна отправка на test_recipient или mass-invite:test-mail --to=user@mail — логин в письме = получатель (если нет --example).
 * mass-invite:send-all --force — по всем recipients, пауза 1–3 с между письмами (опции --min-delay / --max-delay). Сначала --dry-run.
 *
 * Получатели рассылки (фиксированный список, порядок сохраняем):
 */
return [
    /** Получатель одной отправки командой mass-invite:test-mail */
    'test_recipient' => 'kfc.kurochka@gmail.com',

    /**
     * @var list<string>
     */
    'recipients' => [
        'org.nam@mail.ru',
        'anton.pasiy@yandex.ru',
        'nastya.gorbunova.2018@mail.ru',
        'ya-maha@list.ru',
        'smi14@rambler.ru',
        'morenazabore@yandex.ru',
        'nvnosenko@yandex.ru',
        'lexaragulin55@mail.ru',
        'hamer9@yandex.ru',
        'aleksgrand90@mail.ru',
        'latidik@gmail.com',
        'nikita.n.pushkar@yandex.ru',
        'market@konmash.ru',
    ],
];
