<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event === 'nuevo_reporte' ? 'Nuevo ticket de mantenimiento' : 'Actualización de mantenimiento' }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#172033;">
    @php
        $statusLabel = \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status;
        $priorityLabel = \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority;
        $categoryLabel = \App\Models\MaintenanceTicket::CATEGORY_LABELS[$ticket->category] ?? $ticket->category;
        $eventTitle = match ($event) {
            'nuevo_reporte' => 'Nuevo reporte de mantenimiento',
            'asignacion' => 'Ticket de mantenimiento asignado',
            'cierre' => 'Ticket de mantenimiento completado',
            default => 'Actualización de ticket de mantenimiento',
        };
        $eventCopy = match ($event) {
            'nuevo_reporte' => 'Se registró un nuevo reporte de mantenimiento en el sistema.',
            'asignacion' => 'Se actualizó la asignación del técnico o proveedor responsable.',
            'cierre' => 'El ticket fue marcado como completado.',
            default => 'Se actualizó el estado o información operativa del ticket.',
        };
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(20, 31, 50, .08);">
                    <tr>
                        <td align="center" style="padding:34px 32px 22px; background:#fbfaf8;">
                            <img src="{{ $logoUrl }}" width="170" alt="SuHomes" style="display:block; max-width:170px; height:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 42px 18px;">
                            <p style="margin:0 0 10px; font-size:13px; line-height:1.4; color:#a42800; font-weight:700; text-transform:uppercase; letter-spacing:.04em;">
                                Folio #{{ $ticket->display_reference }}
                            </p>
                            <h1 style="margin:0 0 14px; font-size:24px; line-height:1.3; color:#0f1f3d;">{{ $eventTitle }}</h1>
                            <p style="margin:0 0 24px; font-size:16px; line-height:1.65; color:#4b5565;">
                                {{ $eventCopy }} Puedes revisar el detalle, evidencias, historial y seguimiento desde el sistema.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin:0 0 28px;">
                                <tr>
                                    <td style="padding:18px 20px; background:#f9fafb;">
                                        <p style="margin:0 0 6px; font-size:13px; color:#6b7280; font-weight:700;">Ticket</p>
                                        <p style="margin:0; font-size:18px; line-height:1.4; color:#111827; font-weight:700;">{{ $ticket->title }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 18px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="padding:16px 0 0; width:50%; vertical-align:top;">
                                                    <p style="margin:0 0 4px; font-size:12px; color:#6b7280; font-weight:700;">Estado</p>
                                                    <p style="margin:0; font-size:15px; color:#111827;">{{ $statusLabel }}</p>
                                                </td>
                                                <td style="padding:16px 0 0; width:50%; vertical-align:top;">
                                                    <p style="margin:0 0 4px; font-size:12px; color:#6b7280; font-weight:700;">Prioridad</p>
                                                    <p style="margin:0; font-size:15px; color:#111827;">{{ $priorityLabel }}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:16px 0 0; width:50%; vertical-align:top;">
                                                    <p style="margin:0 0 4px; font-size:12px; color:#6b7280; font-weight:700;">Categoría</p>
                                                    <p style="margin:0; font-size:15px; color:#111827;">{{ $categoryLabel }}</p>
                                                </td>
                                                <td style="padding:16px 0 0; width:50%; vertical-align:top;">
                                                    <p style="margin:0 0 4px; font-size:12px; color:#6b7280; font-weight:700;">Propiedad</p>
                                                    <p style="margin:0; font-size:15px; color:#111827;">{{ $ticket->property?->internal_name ?? '-' }}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:16px 0 0; width:50%; vertical-align:top;">
                                                    <p style="margin:0 0 4px; font-size:12px; color:#6b7280; font-weight:700;">Técnico</p>
                                                    <p style="margin:0; font-size:15px; color:#111827;">{{ $ticket->currentProvider?->name ?? 'Sin asignar' }}</p>
                                                </td>
                                                <td style="padding:16px 0 0; width:50%; vertical-align:top;">
                                                    <p style="margin:0 0 4px; font-size:12px; color:#6b7280; font-weight:700;">Visita programada</p>
                                                    <p style="margin:0; font-size:15px; color:#111827;">{{ $ticket->scheduled_visit_at?->format('d/m/Y H:i') ?? 'Sin agenda' }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 30px;">
                                <tr>
                                    <td align="center" bgcolor="#a42800" style="border-radius:8px;">
                                        <a href="{{ $ticketUrl }}" style="display:inline-block; padding:14px 24px; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none; border-radius:8px;">
                                            Abrir ticket en {{ $appName }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:14px; line-height:1.6; color:#6b7280;">
                                Si el sistema te pide iniciar sesión, entra con tu cuenta habitual y volverás al detalle del ticket.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 42px 34px;">
                            <div style="height:1px; background:#e5e7eb; margin:10px 0 22px;"></div>
                            <p style="margin:0 0 10px; font-size:13px; line-height:1.6; color:#7a8291;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>
                            <a href="{{ $ticketUrl }}" style="font-size:13px; line-height:1.6; color:#a42800; word-break:break-all;">{{ $ticketUrl }}</a>
                            <p style="margin:16px 0 0; font-size:13px; line-height:1.6; color:#7a8291;">
                                Acceso al sistema: <a href="{{ $loginUrl }}" style="color:#a42800; text-decoration:none;">{{ $loginUrl }}</a>
                            </p>
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
