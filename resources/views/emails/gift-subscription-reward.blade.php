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
                    Подарок от команды
                </div>
                <div style="margin-top:8px;font-size:13px;line-height:1.5;color:#334155;">
                    Привет! Спасибо за внимательность — мы ценим таких пользователей.
                </div>
            </div>

            <div style="padding:18px;font-size:14px;line-height:1.6;color:#0f172a;">
                <p style="margin:0 0 14px 0;">
                    Кажется, вы нашли способ подружиться с нашими тестовыми ключами чуть ближе, чем мы планировали 😄
                </p>
                <p style="margin:0 0 14px 0;">
                    Мы заметили необычную активность, проверили ситуацию и поняли, что вы обнаружили уязвимость в нашей системе выдачи тестового доступа. Спасибо за внимательность и любознательность — благодаря таким находкам сервис становится лучше и надёжнее для всех пользователей.
                </p>
                <p style="margin:0 0 14px 0;">
                    Вместо бана и суровых писем от отдела «ай-ай-ай» мы решили поступить иначе.
                </p>

                <div style="margin:18px 0;background:#fff7ed;border:2px solid #000000;border-radius:12px;padding:14px 14px;">
                    <div style="font-size:15px;font-weight:900;line-height:1.35;color:#000000;">
                        🎁 Дарим вам 1 месяц бесплатной подписки.
                    </div>
                    <div style="margin-top:8px;font-size:13px;line-height:1.5;color:#334155;">
                        Считаем, что хороший поступок должен вознаграждаться.
                    </div>
                </div>

                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-bottom:8px;">
                    Ваша ссылка для активации
                </div>
                <p style="margin:0 0 16px 0;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;line-height:1.45;color:#0f172a;">
                    {{ $subscriptionUrl }}
                </p>

                <div style="margin-top:4px;margin-bottom:18px;">
                    <a href="{{ $subscriptionUrl }}" style="display:inline-block;padding:12px 16px;border:4px solid #000;background:#ff4d00;color:#fff;font-weight:800;text-decoration:none;text-transform:uppercase;font-size:12px;">
                        Открыть подписку
                    </a>
                </div>

                <p style="margin:0 0 14px 0;">
                    Кстати, если вам нравится пользоваться «{{ $brand }}» бесплатно, у нас есть способ делать это без поиска новых лазеек 😉
                    Приглашайте друзей по <a href="{{ $referralUrl }}" style="color:#000000;font-weight:800;text-decoration:underline;">реферальной программе</a>, получайте бонусы и продлевайте подписку совершенно официально. И нам приятно, и вам выгодно.
                </p>

                <p style="margin:0 0 14px 0;">
                    Спасибо, что помогаете делать сервис лучше.
                </p>

                <p style="margin:0 0 6px 0;">
                    С уважением,<br>
                    <strong>Команда {{ $brand }}</strong>
                </p>

                <p style="margin:14px 0 0 0;font-size:13px;line-height:1.5;color:#475569;">
                    P.S. Следующую уязвимость тоже можно прислать нам напрямую. Это обычно приносит больше бонусов, чем её эксплуатация 😏
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
