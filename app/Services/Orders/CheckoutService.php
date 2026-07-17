<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDurationOption;
use App\Models\User;
use App\Services\Payments\TestPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly TestPaymentService $paymentService,
    ) {}

    public function checkout(
        Product $selectedProduct,
        int $durationOptionId,
        string $customerEmail,
        ?User $user,
        string $checkoutTokenHash,
    ): Order {
        $existingOrder = Order::query()
            ->where('checkout_token_hash', $checkoutTokenHash)
            ->first();

        if ($existingOrder) {
            return $this->loadOrderDetails($existingOrder);
        }

        return DB::transaction(function () use (
            $selectedProduct,
            $durationOptionId,
            $customerEmail,
            $user,
            $checkoutTokenHash,
        ): Order {
            $product = Product::query()
                ->active()
                ->whereKey($selectedProduct->getKey())
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'duration_option_id' => 'Выбранный товар больше недоступен.',
                ]);
            }

            $durationOption = ProductDurationOption::query()
                ->active()
                ->whereKey($durationOptionId)
                ->where('product_id', $product->getKey())
                ->lockForUpdate()
                ->first();

            if (! $durationOption) {
                throw ValidationException::withMessages([
                    'duration_option_id' => 'Выбранный срок недоступен для этого товара.',
                ]);
            }

            $order = Order::query()->firstOrCreate(
                ['checkout_token_hash' => $checkoutTokenHash],
                [
                    'order_number' => $this->generateOrderNumber(),
                    'user_id' => $user?->getKey(),
                    'customer_email' => $customerEmail,
                    'subtotal' => $durationOption->price,
                    'total' => $durationOption->price,
                    'currency' => 'RUB',
                    'payment_method' => 'test',
                    'payment_status' => 'pending',
                    'order_status' => Order::STATUS_PENDING,
                ]
            );

            if (! $order->wasRecentlyCreated) {
                return $this->loadOrderDetails($order);
            }

            $order->items()->create([
                'product_id' => $product->getKey(),
                'product_name' => $product->name,
                'quantity' => 1,
                'unit_price' => $durationOption->price,
                'total_price' => $durationOption->price,
                'duration_days' => $durationOption->duration_days,
            ]);

            $this->paymentService->pay($order);

            return $this->loadOrderDetails($order->refresh());
        });
    }

    private function generateOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = 'NPK-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            if (! Order::query()->where('order_number', $number)->exists()) {
                return $number;
            }
        }

        throw new RuntimeException('Не удалось сформировать уникальный номер заказа.');
    }

    private function loadOrderDetails(Order $order): Order
    {
        return $order->loadMissing(['items', 'paymentTransactions']);
    }
}
