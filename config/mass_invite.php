<?php

/**
 * Рассылка «вход в кабинет»: логин = email, пароль — через восстановление.
 * mass-invite:test-mail — одна отправка на test_recipient (тот же шаблон, что и для рассылки). Массовая отправка по recipients — отдельно.
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
