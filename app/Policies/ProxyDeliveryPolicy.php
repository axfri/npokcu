<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\ProxyDelivery;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProxyDeliveryPolicy
{
    public function download(User $user, ProxyDelivery $proxyDelivery): Response
    {
        if ((int) $proxyDelivery->user_id !== (int) $user->getKey()) {
            return Response::denyAsNotFound();
        }

        $proxyDelivery->loadMissing(['order', 'orderItem']);
        $order = $proxyDelivery->order;
        $orderItem = $proxyDelivery->orderItem;

        if (! $order || ! $orderItem || ! $proxyDelivery->matchesContext($order, $orderItem)) {
            return Response::denyAsNotFound();
        }

        if (
            $order->payment_status !== PaymentTransaction::STATUS_PAID
            || $order->order_status !== Order::STATUS_COMPLETED
        ) {
            return Response::deny('Заказ недоступен для скачивания.');
        }

        return $proxyDelivery->isDownloadable()
            ? Response::allow()
            : Response::deny('Файл недоступен для скачивания.');
    }
}
