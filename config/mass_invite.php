<?php

/**
 * Рассылка «вход в кабинет»: логин = email, пароль — через восстановление.
 * Команда mass-invite:test-mail шлёт одно письмо на test_recipient; массовая отправка по recipients — вручную, когда будете готовы.
 */
return [
    /** Куда уходит письмо при artisan mass-invite:test-mail (один адрес) */
    'test_recipient' => 'kfc.kurochka@gmail.com',

    /**
     * Получатели будущей рассылки (пока только храним список).
     *
     * @var list<string>
     */
    'recipients' => [
        'nikita.n.pushkar@yandex.ru',
        'latidik@gmail.com',
        'aleksgrand90@mail.ru',
        'hamer9@yandex.ru',
        'lexaragulin55@mail.ru',
        'nvnosenko@yandex.ru',
        'morenazabore@yandex.ru',
        'smi14@rambler.ru',
        'ya-maha@list.ru',
        'nastya.gorbunova.2018@mail.ru',
        'anton.pasiy@yandex.ru',
        'org.nam@mail.ru',
        '79659892384@ya.ru',
    ],
];
