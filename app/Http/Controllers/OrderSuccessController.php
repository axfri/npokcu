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
            'items' => fn ($query) => $query->orderBy('id'),
            'paymentTransactions' => fn ($query) => $query->latest('id'),
        ]);

        return view('orders.success', compact('order'));
    }
}
