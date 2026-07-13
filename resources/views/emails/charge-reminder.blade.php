<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recordatorio de pago</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2 style="margin-bottom: 10px;">Recordatorio de pago</h2>
    <p>Hola {{ $charge->tenant?->full_name ?? 'inquilino' }},</p>
    <p>
        Este es un recordatorio de tu cargo próximo a vencer.
        {{ $daysBefore > 0 ? "Faltan {$daysBefore} días para el vencimiento." : 'Este cargo vence hoy.' }}
    </p>

    <ul>
        <li><strong>Concepto:</strong> {{ $charge->concept }}</li>
        <li><strong>Saldo pendiente:</strong> ${{ number_format((float) $charge->outstanding_amount, 2) }} MXN</li>
        <li><strong>Vencimiento:</strong> {{ $charge->due_date?->format('d/m/Y') }}</li>
    </ul>

    @if (filled($customMessage))
        <p><strong>Mensaje adicional:</strong><br>{{ $customMessage }}</p>
    @endif
</body>

</html>
