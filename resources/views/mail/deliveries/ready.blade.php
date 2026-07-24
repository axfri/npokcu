<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Файл по заказу</title>
</head>
<body>
    <h1>Файл подготовлен</h1>
    <p>Заказ: {{ $delivery->order->order_number }}</p>
    <p>Товар: {{ $delivery->orderItem->product_name }}</p>
    <p>Срок действия: {{ $delivery->orderItem->duration_days }} дней.</p>
    @if ($delivery->expires_at)
        <p>Действует до {{ $delivery->expires_at->format('d.m.Y H:i') }}.</p>
    @endif
    <p>Тестовый файл прикреплён к этому письму и также доступен в личном кабинете.</p>
    <p><a href="{{ route('account') }}">Открыть личный кабинет</a></p>
</body>
</html>
