<?php

return [
    /*
     * Сколько часов метка из t.me/{bot}?start=МЕТКА остаётся действительной, если
     * человек нажал /start, а «Новый пользователь» — заметно позже (или бот успел
     * перезапуститься и потерял метку из памяти).
     */
    'attribution_window_hours' => (int) env('CAMPAIGN_ATTRIBUTION_WINDOW_HOURS', 72),
];
