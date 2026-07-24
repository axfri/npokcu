<?php

namespace App\Services\Deliveries;

use App\Mail\ProxyDeliveryMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\ProxyDelivery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProxyDeliveryService
{
    private const FILE_CONTENT = 'hello';

    public function deliver(Order $order): Order
    {
        $deliveryIds = [];
        $activatedDeliveryIds = [];

        try {
            $deliveries = $this->prepareDeliveries($order);
            $deliveryIds = $deliveries->modelKeys();
            $deliveryIdsForMail = $this->writeFiles($deliveries);
            [$completedOrder, $activatedDeliveryIds] = $this->activateDeliveries(
                $order,
                $deliveryIds,
                $deliveryIdsForMail,
            );
        } catch (Throwable $exception) {
            $this->markAsFailed($order, $deliveryIds);
            Log::error('Не удалось подготовить тестовую выдачу заказа.', [
                'order_id' => $order->getKey(),
                'delivery_ids' => $deliveryIds,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        try {
            $this->queueMail($activatedDeliveryIds);
        } catch (Throwable $exception) {
            Log::error('Не удалось поставить письмо о тестовой выдаче в очередь.', [
                'order_id' => $order->getKey(),
                'delivery_ids' => $activatedDeliveryIds,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $completedOrder;
    }

    /**
     * @return Collection<int, ProxyDelivery>
     */
    private function prepareDeliveries(Order $order): Collection
    {
        return DB::transaction(function () use ($order): Collection {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureOrderCanBeDelivered($lockedOrder);

            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new RuntimeException('В оплаченном заказе нет позиций для выдачи.');
            }

            return $items->map(function (OrderItem $item) use ($lockedOrder): ProxyDelivery {
                if ($item->starts_at === null || $item->expires_at === null) {
                    throw new RuntimeException('Для позиции заказа не определён срок действия.');
                }

                $delivery = ProxyDelivery::query()
                    ->where('order_item_id', $item->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($delivery) {
                    if (
                        (int) $delivery->order_id !== (int) $lockedOrder->getKey()
                        || (int) $delivery->user_id !== (int) $lockedOrder->user_id
                    ) {
                        throw new RuntimeException('Выдача позиции связана с другим заказом или пользователем.');
                    }

                    if (! $delivery->matchesContext($lockedOrder, $item)) {
                        throw new RuntimeException('Выдача содержит недопустимый приватный путь.');
                    }

                    return $delivery;
                }

                $filename = $this->generateFilename($lockedOrder);

                return ProxyDelivery::query()->create([
                    'user_id' => $lockedOrder->user_id,
                    'order_id' => $lockedOrder->getKey(),
                    'order_item_id' => $item->getKey(),
                    'file_path' => $lockedOrder->user_id.'/'.$lockedOrder->order_number.'/'.$filename,
                    'original_filename' => $filename,
                    'status' => ProxyDelivery::STATUS_PENDING,
                    'starts_at' => $item->starts_at,
                    'expires_at' => $item->expires_at,
                    'download_count' => 0,
                ]);
            });
        }, 3);
    }

    private function ensureOrderCanBeDelivered(Order $order): void
    {
        if ($order->user_id === null) {
            throw new RuntimeException('Оплаченный заказ не привязан к пользователю.');
        }

        if (in_array($order->order_status, [Order::STATUS_CANCELLED, Order::STATUS_FAILED], true)) {
            throw new RuntimeException('Отменённый заказ или заказ с ошибкой нельзя выдать.');
        }

        if (
            $order->payment_status !== PaymentTransaction::STATUS_PAID
            || $order->paid_at === null
            || ! $order->paymentTransactions()
                ->where('status', PaymentTransaction::STATUS_PAID)
                ->exists()
        ) {
            throw new RuntimeException('Файл можно подготовить только для оплаченного заказа.');
        }
    }

    /**
     * @param  Collection<int, ProxyDelivery>  $deliveries
     * @return array<int>
     */
    private function writeFiles(Collection $deliveries): array
    {
        $disk = Storage::disk($this->disk());
        $deliveryIdsForMail = [];

        foreach ($deliveries as $delivery) {
            $fileExists = $disk->exists($delivery->file_path);

            if ($delivery->status === ProxyDelivery::STATUS_ACTIVE && $fileExists) {
                continue;
            }

            if (! $disk->put($delivery->file_path, self::FILE_CONTENT)) {
                throw new RuntimeException('Приватный файл выдачи не был сохранён.');
            }

            if ($delivery->status !== ProxyDelivery::STATUS_ACTIVE) {
                $deliveryIdsForMail[] = (int) $delivery->getKey();
            }
        }

        return $deliveryIdsForMail;
    }

    /**
     * @param  array<int>  $deliveryIds
     * @param  array<int>  $deliveryIdsForMail
     * @return array{Order, array<int>}
     */
    private function activateDeliveries(
        Order $order,
        array $deliveryIds,
        array $deliveryIdsForMail,
    ): array {
        return DB::transaction(function () use ($order, $deliveryIds, $deliveryIdsForMail): array {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureOrderCanBeDelivered($lockedOrder);

            $deliveries = ProxyDelivery::query()
                ->where('order_id', $lockedOrder->getKey())
                ->whereIn('id', $deliveryIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($deliveries->count() !== count($deliveryIds)) {
                throw new RuntimeException('Не все выдачи заказа были найдены.');
            }

            $disk = Storage::disk($this->disk());
            $activatedDeliveryIds = [];

            foreach ($deliveries as $delivery) {
                if (! $disk->exists($delivery->file_path)) {
                    throw new RuntimeException('Приватный файл выдачи не найден после сохранения.');
                }

                if (
                    $delivery->status !== ProxyDelivery::STATUS_ACTIVE
                    && in_array((int) $delivery->getKey(), $deliveryIdsForMail, true)
                ) {
                    $delivery->forceFill([
                        'status' => ProxyDelivery::STATUS_ACTIVE,
                        'delivered_at' => $delivery->delivered_at ?? now(),
                    ])->save();
                    $activatedDeliveryIds[] = (int) $delivery->getKey();
                }
            }

            $itemCount = $lockedOrder->items()->count();
            $activeCount = $lockedOrder->proxyDeliveries()
                ->where('status', ProxyDelivery::STATUS_ACTIVE)
                ->count();

            if ($itemCount === 0 || $activeCount !== $itemCount) {
                throw new RuntimeException('Заказ нельзя завершить до подготовки всех файлов.');
            }

            if ($lockedOrder->order_status !== Order::STATUS_COMPLETED) {
                $lockedOrder->forceFill([
                    'order_status' => Order::STATUS_COMPLETED,
                    'completed_at' => now(),
                ])->save();
            }

            return [
                $lockedOrder->load(['items.proxyDelivery', 'paymentTransactions']),
                $activatedDeliveryIds,
            ];
        }, 3);
    }

    /**
     * @param  array<int>  $deliveryIds
     */
    private function queueMail(array $deliveryIds): void
    {
        if ($deliveryIds === []) {
            return;
        }

        ProxyDelivery::query()
            ->whereIn('id', $deliveryIds)
            ->with(['order', 'orderItem'])
            ->orderBy('id')
            ->get()
            ->each(function (ProxyDelivery $delivery): void {
                Mail::mailer((string) config('mail.delivery_mailer', 'smtp'))
                    ->to($delivery->order->customer_email)
                    ->queue(new ProxyDeliveryMail($delivery));
            });
    }

    /**
     * @param  array<int>  $deliveryIds
     */
    private function markAsFailed(Order $order, array $deliveryIds): void
    {
        if ($deliveryIds === []) {
            return;
        }

        try {
            DB::transaction(function () use ($order, $deliveryIds): void {
                $lockedOrder = Order::query()
                    ->whereKey($order->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $lockedOrder) {
                    return;
                }

                ProxyDelivery::query()
                    ->where('order_id', $lockedOrder->getKey())
                    ->whereIn('id', $deliveryIds)
                    ->where('status', '!=', ProxyDelivery::STATUS_ACTIVE)
                    ->update([
                        'status' => ProxyDelivery::STATUS_FAILED,
                        'delivered_at' => null,
                        'updated_at' => now(),
                    ]);

                if (in_array($lockedOrder->order_status, [
                    Order::STATUS_PENDING,
                    Order::STATUS_AWAITING_PAYMENT,
                    Order::STATUS_PAID,
                    Order::STATUS_PROCESSING,
                ], true)) {
                    $lockedOrder->forceFill([
                        'order_status' => Order::STATUS_PROCESSING,
                        'completed_at' => null,
                    ])->save();
                }
            }, 3);
        } catch (Throwable $failure) {
            Log::critical('Не удалось зафиксировать ошибку тестовой выдачи.', [
                'order_id' => $order->getKey(),
                'exception' => $failure::class,
                'message' => $failure->getMessage(),
            ]);
        }
    }

    private function generateFilename(Order $order): string
    {
        return 'proxy-'.$order->order_number.'-'.Str::upper(Str::random(10)).'.txt';
    }

    private function disk(): string
    {
        return (string) config('deliveries.disk', 'private');
    }
}
