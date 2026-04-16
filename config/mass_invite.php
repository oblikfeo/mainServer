<?php

/**
 * Рассылка «вход в кабинет»: логин = email, пароль — через восстановление.
 * Реальная отправка по списку — отдельно, когда будете готовы (не включено в команду по умолчанию).
 */
return [
    /** Тестовая отправка шаблона (один размер) */
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
