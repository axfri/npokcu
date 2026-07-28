<?php

namespace App\Support;

final class AdminLabels
{
    public static function orderStatus(string $status): string
    {
        return match ($status) {
            'awaiting_payment' => 'Ожидает оплаты',
            'paid' => 'Оплачен',
            'processing' => 'В обработке',
            'completed' => 'Завершён',
            'cancelled' => 'Отменён',
            'failed' => 'Ошибка',
            default => 'Ожидает обработки',
        };
    }

    public static function paymentStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'Оплачено',
            'failed' => 'Ошибка',
            'cancelled' => 'Отменено',
            default => 'Ожидает оплаты',
        };
    }

    public static function deliveryStatus(string $status): string
    {
        return match ($status) {
            'ready' => 'Подготовлен',
            'delivered' => 'Доставлен',
            'active' => 'Активен',
            'expired' => 'Истёк',
            'failed' => 'Ошибка',
            default => 'Ожидает выдачи',
        };
    }

    public static function variant(string $status): string
    {
        return match ($status) {
            'paid', 'completed', 'ready', 'delivered', 'active' => 'success',
            'processing', 'awaiting_payment', 'pending' => 'warning',
            'failed' => 'danger',
            default => 'neutral',
        };
    }
}
