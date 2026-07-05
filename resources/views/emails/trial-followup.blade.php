<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
</head>
<body style="margin:0;padding:0;background:#f8fafc;color:#0f172a;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;">
    <div style="max-width:640px;margin:0 auto;padding:24px 14px;">
        <div style="background:#ffffff;border:4px solid #000000;border-radius:14px;overflow:hidden;">
            <div style="padding:18px 18px 14px 18px;border-bottom:4px solid #000000;">
                <div style="font-weight:900;letter-spacing:-0.02em;text-transform:uppercase;font-size:14px;line-height:1.2;">
                    {{ $brand }}
                </div>
                <div style="margin-top:6px;font-weight:900;font-size:22px;line-height:1.15;letter-spacing:-0.02em;color:#000000;">
                    А че случилось?)
                </div>
            </div>

            <div style="padding:18px;font-size:14px;line-height:1.6;color:#0f172a;">
                <p style="margin:0 0 14px 0;">Привет!</p>

                <p style="margin:0 0 14px 0;">
                    Вы попробовали {{ $brand }} на тестовом ключе — и пропали. Мы, конечно, не обижаемся (почти), но честно переживаем: у нас всё получилось или где-то споткнулись?
                </p>

                <p style="margin:0 0 14px 0;">
                    Это не рассылка ради галочки — нам правда важно понять. Подскажите в двух словах, что было ближе к правде:
                </p>

                <ul style="margin:0 0 14px 0;padding-left:20px;">
                    <li style="margin:0 0 8px 0;">Всё понравилось, просто руки не дошли оплатить</li>
                    <li style="margin:0 0 8px 0;">Не разобрался, как подключить ключ или продлить подписку</li>
                    <li style="margin:0 0 8px 0;">Что-то не так пошло с приложением Happ</li>
                    <li style="margin:0 0 8px 0;">Не хватило скорости или стабильности</li>
                    <li style="margin:0 0 0 0;">Другое (расскажите своими словами — мы читаем всё)</li>
                </ul>

                <p style="margin:0 0 14px 0;">
                    Ответить можно прямо на это письмо, одной строкой. Для нас каждый такой ответ — это то, из чего мы буквально строим сервис дальше.
                </p>

                <p style="margin:0 0 14px 0;">
                    А чтобы поблагодарить за потраченную минуту — держите ключ ещё на сутки, просто так:
                </p>

                <div style="margin:18px 0;background:#fff7ed;border:2px solid #000000;border-radius:12px;padding:14px 14px;">
                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-bottom:8px;">
                        Ключ-подписка на сутки
                    </div>
                    <p style="margin:0 0 12px 0;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;line-height:1.45;color:#0f172a;">
                        {{ $subscriptionUrl }}
                    </p>
                    <a href="{{ $subscriptionUrl }}" style="display:inline-block;padding:12px 16px;border:4px solid #000;background:#ff4d00;color:#fff;font-weight:800;text-decoration:none;text-transform:uppercase;font-size:12px;">
                        Открыть ключ
                    </a>
                </div>

                <p style="margin:0 0 14px 0;">
                    Вставьте его в Happ вместо прежнего — и пользуйтесь. Если как раз на этом шаге в прошлый раз что-то не сложилось, ответьте на письмо, и живой человек (не бот) поможет за пару сообщений.
                </p>

                <p style="margin:0 0 14px 0;">
                    И пара слов о том, почему мы вообще пристаём с вопросами. {{ $brand }} мы делали в первую очередь для себя, родителей и друзей — чтобы у близких просто работал интернет, без танцев с настройками. Мы сами каждый день ей пользуемся, поэтому и допиливаем без остановки: новые серверы, обход блокировок, скорость. Ваш ответ реально попадает в эту работу, а не в пустоту.
                </p>

                <p style="margin:0 0 14px 0;">Попробуете ещё раз?</p>

                <div style="margin:8px 0 18px 0;">
                    <a href="{{ $paymentUrl }}" style="display:inline-block;padding:12px 16px;border:4px solid #000;background:#ffffff;color:#000000;font-weight:800;text-decoration:none;text-transform:uppercase;font-size:12px;">
                        Оформить подписку
                    </a>
                </div>

                <p style="margin:0 0 6px 0;">
                    С теплом,<br>
                    <strong>команда {{ $brand }}</strong>
                </p>
            </div>

            <div style="padding:14px 18px;border-top:4px solid #000000;background:#ffffff;">
                <div style="font-size:12px;line-height:1.5;color:#475569;">
                    Поддержка: <a href="mailto:{{ $supportEmail }}" style="color:#000000;font-weight:800;text-decoration:underline;">{{ $supportEmail }}</a>
                </div>
            </div>
        </div>

        <div style="margin-top:10px;font-size:11px;line-height:1.5;color:#64748b;text-align:center;">
            {{ $brand }} · автоматическое письмо
        </div>
    </div>
</body>
</html>
