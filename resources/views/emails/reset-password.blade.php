<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablece tu contraseña</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(20, 31, 50, .08);">
                    <tr>
                        <td align="center" style="padding:34px 32px 22px; background:#fbfaf8;">
                            <img src="{{ $logoUrl }}" width="170" alt="SuHomes" style="display:block; max-width:170px; height:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 42px 18px;">
                            <h1 style="margin:0 0 16px; font-size:24px; line-height:1.3; color:#0f1f3d;">Restablece tu contraseña</h1>
                            <p style="margin:0 0 18px; font-size:16px; line-height:1.65; color:#4b5565;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta en {{ $appName }}.
                            </p>
                            <p style="margin:0 0 28px; font-size:16px; line-height:1.65; color:#4b5565;">
                                Haz clic en el siguiente botón para crear una nueva contraseña.
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 30px;">
                                <tr>
                                    <td align="center" bgcolor="#a42800" style="border-radius:8px;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block; padding:14px 24px; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none; border-radius:8px;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 12px; font-size:14px; line-height:1.6; color:#6b7280;">
                                Este enlace caduca en {{ $expirationMinutes }} minutos.
                            </p>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#6b7280;">
                                Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña actual seguirá siendo válida.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 42px 34px;">
                            <div style="height:1px; background:#e5e7eb; margin:10px 0 22px;"></div>
                            <p style="margin:0 0 10px; font-size:13px; line-height:1.6; color:#7a8291;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>
                            <a href="{{ $resetUrl }}" style="font-size:13px; line-height:1.6; color:#a42800; word-break:break-all;">{{ $resetUrl }}</a>
                            <p style="margin:22px 0 0; font-size:14px; line-height:1.6; color:#4b5565;">
                                Saludos,<br>
                                Equipo {{ $appName }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
