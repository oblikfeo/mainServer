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
                    Ваша подписка
                </div>
                <div style="margin-top:8px;font-size:13px;line-height:1.5;color:#334155;">
                    Оплата прошла успешно. Ниже — ссылка для добавления в Happ и вход в личный кабинет.
                </div>
            </div>

            <div style="padding:18px;">
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-bottom:8px;">
                    Ссылка подписки
                </div>
                <p style="margin:0 0 16px 0;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;line-height:1.45;color:#0f172a;">
                    {{ $subscriptionUrl }}
                </p>

                <div style="margin-top:14px;background:#f1f5f9;border:2px solid #000000;border-radius:12px;padding:12px 12px;">
                    <div style="font-size:12px;line-height:1.5;color:#0f172a;">
                        В Happ нажмите ⓘ — откроется личный кабинет на сайте без ввода пароля.
                    </div>
                </div>

                <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:10px;">
                    <a href="{{ $cabinetLoginUrl }}" style="display:inline-block;padding:12px 16px;border:4px solid #000;background:#ff4d00;color:#fff;font-weight:800;text-decoration:none;text-transform:uppercase;font-size:12px;">
                        Войти в кабинет
                    </a>
                </div>

                @if (! empty($referralLink))
                    <div style="margin-top:18px;padding:14px 12px;background:#fff7ed;border:2px solid #000;border-radius:12px;">
                        <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-bottom:8px;">
                            Реферальная программа
                        </div>
                        <div style="font-size:13px;line-height:1.5;color:#0f172a;margin-bottom:10px;">
                            Поделитесь ссылкой с друзьями — за каждого приглашённого начисляются бонусы к подписке.
                        </div>
                        <p style="margin:0;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;line-height:1.45;color:#0f172a;">
                            {{ $referralLink }}
                        </p>
                    </div>
                @endif

                <div style="margin-top:14px;font-size:12px;line-height:1.5;color:#475569;">
                    Сайт: <a href="{{ $appUrl }}/dashboard" style="color:#000000;font-weight:800;text-decoration:underline;">{{ $appUrl }}/dashboard</a>
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
