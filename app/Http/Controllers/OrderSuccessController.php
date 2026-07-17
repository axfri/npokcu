<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderSuccessController extends Controller
{
    public function __invoke(Request $request, Order $order): View
    {
        if ($order->user_id !== null) {
            abort_unless(
                $request->user()
                && (int) $request->user()->getKey() === (int) $order->user_id,
                404
            );
        } else {
            abort_unless($request->hasValidSignature(), 404);
        }

        $order->load([
            'items' => fn ($query) => $query->orderBy('id'),
            'paymentTransactions' => fn ($query) => $query->latest('id'),
        ]);

        return view('orders.success', compact('order'));
    }
}
