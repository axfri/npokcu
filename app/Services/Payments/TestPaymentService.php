<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TestPaymentService
{
    public function pay(Order $order): PaymentTransaction
    {
        if (! config('payments.test_mode')) {
            throw new RuntimeException('Тестовая оплата отключена.');
        }

        return DB::transaction(function () use ($order): PaymentTransaction {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $externalPaymentId = 'test-'.$lockedOrder->order_number;
            $payment = PaymentTransaction::query()->firstOrCreate(
                [
                    'provider' => 'test',
                    'external_payment_id' => $externalPaymentId,
                ],
                [
                    'order_id' => $lockedOrder->getKey(),
                    'amount' => $lockedOrder->total,
                    'currency' => $lockedOrder->currency,
                    'status' => PaymentTransaction::STATUS_PENDING,
                    'request_payload' => ['mode' => 'test'],
                ]
            );

            if ($payment->status === PaymentTransaction::STATUS_PAID) {
                return $payment;
            }

            $paidAt = now();

            $lockedOrder->items()
                ->lockForUpdate()
                ->get()
                ->each(function ($item) use ($paidAt): void {
                    $item->forceFill([
                        'starts_at' => $paidAt,
                        'expires_at' => $paidAt->copy()->addDays($item->duration_days),
                    ])->save();
                });

            $payment->forceFill([
                'order_id' => $lockedOrder->getKey(),
                'amount' => $lockedOrder->total,
                'currency' => $lockedOrder->currency,
                'status' => PaymentTransaction::STATUS_PAID,
                'response_payload' => ['result' => 'paid', 'mode' => 'test'],
                'paid_at' => $paidAt,
            ])->save();

            $lockedOrder->forceFill([
                'payment_method' => 'test',
                'payment_status' => PaymentTransaction::STATUS_PAID,
                'order_status' => Order::STATUS_PROCESSING,
                'paid_at' => $paidAt,
            ])->save();

            return $payment->refresh();
        });
    }
}
