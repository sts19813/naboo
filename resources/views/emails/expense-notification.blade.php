<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Notificación de gasto</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2 style="margin-bottom: 10px;">
        {{ $trigger === \App\Mail\ExpenseNotificationMail::TRIGGER_OVERDUE ? 'Gasto vencido' : 'Gasto próximo a vencer' }}
    </h2>

    <p>
        @if ($trigger === \App\Mail\ExpenseNotificationMail::TRIGGER_OVERDUE)
            El siguiente gasto se encuentra vencido y requiere atención.
        @else
            Este gasto vence próximamente (ventana de aviso: {{ $daysBefore }} días).
        @endif
    </p>

    <ul>
        <li><strong>Propiedad:</strong> {{ $expense->property?->internal_name ?? '-' }}</li>
        <li><strong>Concepto:</strong> {{ $expense->concept }}</li>
        <li><strong>Monto:</strong> ${{ number_format((float) $expense->amount, 2) }} MXN</li>
        <li><strong>Vencimiento:</strong> {{ $expense->due_date?->format('d/m/Y') ?? '-' }}</li>
        <li><strong>Estado:</strong> {{ $expense->status_label }}</li>
    </ul>
</body>

</html>
