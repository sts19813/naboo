<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pago confirmado</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2 style="margin-bottom: 10px;">Pago confirmado</h2>
    <p>Hola {{ $charge->tenant?->full_name ?? 'inquilino' }},</p>
    <p>Confirmamos que tu cargo fue cubierto en su totalidad.</p>

    <ul>
        <li><strong>Concepto:</strong> {{ $charge->concept }}</li>
        <li><strong>Monto:</strong> ${{ number_format((float) $charge->amount, 2) }} MXN</li>
        <li><strong>Periodo:</strong> {{ str_pad((string) $charge->period_month, 2, '0', STR_PAD_LEFT) }}/{{ $charge->period_year }}</li>
        <li><strong>Fecha de pago:</strong> {{ $charge->paid_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</li>
    </ul>

    <p>Gracias.</p>
</body>

</html>
