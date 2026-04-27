<?php

/**
 * Рассылка «вход в кабинет»: логин = email, пароль — через восстановление.
 * Команда mass-invite:test-mail шлёт одно письмо на test_recipient; массовая отправка по recipients — вручную, когда будете готовы.
 *
 * Получатели рассылки (фиксированный список, порядок сохраняем):
 */
return [
    /** Куда уходит письмо при artisan mass-invite:test-mail (один адрес для просмотра) */
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
