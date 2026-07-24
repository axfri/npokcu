<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderSuccessController extends Controller
{
    public function __invoke(Request $request, Order $order): View
    {
        $isOwner = $request->user()
            && (int) $request->user()->getKey() === (int) $order->user_id;
        $hasGuestSignature = $request->user() === null
            && $order->guest_account_status !== null
            && $request->hasValidSignature();

        abort_unless($isOwner || $hasGuestSignature, 404);

        $order->load([
            'items' => fn ($query) => $query->with('proxyDelivery')->orderBy('id'),
            'paymentTransactions' => fn ($query) => $query->latest('id'),
        ]);
        $order->items->each(function ($item) use ($order): void {
            $item->proxyDelivery?->setRelation('order', $order);
            $item->proxyDelivery?->setRelation('orderItem', $item);
        });

        return view('orders.success', compact('order', 'isOwner'));
    }
}
