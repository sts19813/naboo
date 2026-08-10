<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Propiedad asignada con tickets pendientes</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#1f2632;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px; background:#f4f6f8;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px; overflow:hidden; background:#fff; border-radius:14px; box-shadow:0 10px 30px rgba(20,31,50,.08);">
                    <tr>
                        <td align="center" style="padding:32px; background:#fbfaf8;">
                            <img src="{{ $logoUrl }}" width="170" alt="{{ $appName }}" style="display:block; max-width:170px; height:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 42px;">
                            <p style="margin:0 0 10px; color:#ff3364; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.04em;">Nueva propiedad asignada</p>
                            <h1 style="margin:0 0 14px; color:#0f1f3d; font-size:24px; line-height:1.3;">Hola, {{ $technician->name }}</h1>
                            <p style="margin:0 0 24px; color:#4b5565; font-size:16px; line-height:1.65;">
                                Ahora eres responsable de <strong>{{ $property->internal_name }}</strong>.
                                @if ($tickets->count() === 1)
                                    El siguiente ticket pendiente fue reasignado a tu cuenta:
                                @else
                                    Los siguientes tickets pendientes fueron reasignados a tu cuenta:
                                @endif
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:26px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                                @foreach ($tickets as $ticket)
                                    <tr>
                                        <td style="padding:16px 18px; border-bottom:{{ $loop->last ? '0' : '1px solid #e5e7eb' }};">
                                            <a href="{{ route('maintenance.show', $ticket) }}" style="color:#0f1f3d; font-size:16px; font-weight:700; text-decoration:none;">#{{ $ticket->display_reference }} · {{ $ticket->title }}</a>
                                            <p style="margin:6px 0 0; color:#6b7280; font-size:13px; line-height:1.5;">
                                                {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }} · {{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}
                                            </p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 28px;">
                                <tr>
                                    <td bgcolor="#ff3364" style="border-radius:8px;">
                                        <a href="{{ $maintenanceUrl }}" style="display:inline-block; padding:14px 24px; color:#fff; font-size:15px; font-weight:700; text-decoration:none;">Ver tickets pendientes</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; color:#7a8291; font-size:13px; line-height:1.6;">
                                Acceso al sistema: <a href="{{ $loginUrl }}" style="color:#ff3364; text-decoration:none;">{{ $loginUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
