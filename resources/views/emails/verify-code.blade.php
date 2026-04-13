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
                    Код подтверждения почты
                </div>
                <div style="margin-top:8px;font-size:13px;line-height:1.5;color:#334155;">
                    Введите этот код в личном кабинете, чтобы подтвердить ваш email.
                </div>
            </div>

            <div style="padding:18px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:18px auto 14px auto;border-collapse:separate;border-spacing:12px 0;">
                    <tr>
                        @foreach (str_split($code) as $digit)
                            <td align="center" valign="middle" style="width:72px;height:72px;border:3px solid #000000;border-radius:14px;background:#ffffff;">
                                <span style="display:inline-block;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,monospace;font-weight:900;font-size:34px;line-height:1;color:#000000;letter-spacing:0.02em;">
                                    {{ $digit }}
                                </span>
                            </td>
                        @endforeach
                    </tr>
                </table>

                <div style="margin-top:14px;background:#f1f5f9;border:2px solid #000000;border-radius:12px;padding:12px 12px;">
                    <div style="font-size:12px;line-height:1.5;color:#0f172a;">
                        Если вы не запрашивали код, просто проигнорируйте это письмо.
                    </div>
                </div>

                <div style="margin-top:14px;font-size:12px;line-height:1.5;color:#475569;">
                    Кабинет: <a href="{{ $appUrl }}/dashboard" style="color:#000000;font-weight:800;text-decoration:underline;">{{ $appUrl }}/dashboard</a>
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

