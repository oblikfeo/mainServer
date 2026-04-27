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
                    Вход в личный кабинет
                </div>
                <div style="margin-top:10px;font-size:13px;line-height:1.55;color:#334155;">
                    Коротко: мы сделали личный кабинет, чтобы вам было удобно управлять подпиской и реферальной программой.
                </div>
                <div style="margin-top:8px;font-size:13px;line-height:1.55;color:#334155;">
                    Ниже — ваш логин. Пароль задайте через «Восстановить пароль»; в письмах пароли не присылаем.
                </div>
            </div>

            <div style="padding:18px;">
                <div style="margin-top:4px;font-size:13px;line-height:1.5;color:#0f172a;">
                    <strong>Ваш логин:</strong>
                </div>
                <p style="margin:10px 0 18px 0;text-align:center;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,monospace;font-size:16px;font-weight:700;line-height:1.35;color:#000000;word-break:break-all;">
                    {{ $loginEmail }}
                </p>

                <div style="text-align:center;margin:8px 0 18px 0;">
                    <a href="{{ $forgotPasswordUrl }}" style="display:inline-block;background:#FF4500;color:#ffffff;font-weight:900;text-decoration:none;padding:14px 26px;border-radius:12px;border:2px solid #cc3700;font-size:15px;line-height:1.2;">
                        Восстановить пароль
                    </a>
                </div>

                <div style="margin-top:14px;background:#f1f5f9;border:2px solid #000000;border-radius:12px;padding:12px 12px;">
                    <div style="font-size:12px;line-height:1.5;color:#0f172a;">
                        На странице восстановления введите этот же email — придёт ссылка для установки нового пароля.
                    </div>
                </div>

                <div style="margin-top:14px;font-size:12px;line-height:1.5;color:#475569;word-break:break-all;">
                    Если кнопка не открывается, скопируйте ссылку:<br>
                    <a href="{{ $forgotPasswordUrl }}" style="color:#000000;font-weight:800;text-decoration:underline;">{{ $forgotPasswordUrl }}</a>
                </div>

                <div style="margin-top:14px;font-size:12px;line-height:1.5;color:#475569;">
                    Сайт: <a href="{{ $appUrl }}" style="color:#000000;font-weight:800;text-decoration:underline;">{{ $appUrl }}</a>
                </div>
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
