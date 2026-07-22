<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Данные для входа</title>
</head>
<body>
    <h1>Аккаунт создан автоматически</h1>
    <p>После оплаты заказа {{ $orderNumber }} для вас был создан аккаунт.</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Временный пароль:</strong> {{ $temporaryPassword }}</p>
    <p><a href="{{ route('login') }}">Войти в личный кабинет</a></p>
    <p>Сразу после входа установите новый пароль. Временный пароль больше нигде не показывается.</p>
</body>
</html>
