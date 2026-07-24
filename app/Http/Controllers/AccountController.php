<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $orders = $user->orders()
            ->where('payment_status', PaymentTransaction::STATUS_PAID)
            ->with(['items.proxyDelivery'])
            ->latest('paid_at')
            ->latest('id')
            ->get();
        $purchases = $orders
            ->flatMap(function ($order) {
                return $order->items->map(function (OrderItem $item) use ($order): OrderItem {
                    $item->setRelation('order', $order);
                    $item->proxyDelivery?->setRelation('order', $order);
                    $item->proxyDelivery?->setRelation('orderItem', $item);

                    return $item;
                });
            })
            ->values();

        return view('account.index', [
            'user' => $user,
            'activePurchases' => $purchases
                ->filter(fn (OrderItem $item): bool => $item->isPurchaseActive())
                ->values(),
            'processingPurchases' => $purchases
                ->filter(fn (OrderItem $item): bool => $item->isPurchaseProcessing())
                ->values(),
            'purchaseHistory' => $purchases
                ->reject(fn (OrderItem $item): bool => $item->isPurchaseActive() || $item->isPurchaseProcessing())
                ->values(),
        ]);
    }
}
