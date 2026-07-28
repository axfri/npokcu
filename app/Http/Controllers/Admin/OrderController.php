<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'order_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'payment_status' => [
                'nullable',
                Rule::in([
                    PaymentTransaction::STATUS_PENDING,
                    PaymentTransaction::STATUS_PAID,
                    PaymentTransaction::STATUS_FAILED,
                    PaymentTransaction::STATUS_CANCELLED,
                ]),
            ],
            'order_status' => [
                'nullable',
                Rule::in([
                    Order::STATUS_PENDING,
                    Order::STATUS_AWAITING_PAYMENT,
                    Order::STATUS_PAID,
                    Order::STATUS_PROCESSING,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED,
                    Order::STATUS_FAILED,
                ]),
            ],
        ]);

        $orders = Order::query()
            ->with('user:id,email')
            ->when(
                $filters['order_number'] ?? null,
                fn ($query, $number) => $query->where('order_number', 'like', '%'.$number.'%'),
            )
            ->when(
                $filters['email'] ?? null,
                fn ($query, $email) => $query->where('customer_email', 'like', '%'.$email.'%'),
            )
            ->when(
                $filters['payment_status'] ?? null,
                fn ($query, $status) => $query->where('payment_status', $status),
            )
            ->when(
                $filters['order_status'] ?? null,
                fn ($query, $status) => $query->where('order_status', $status),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'filters'));
    }

    public function show(Order $order): View
    {
        $order->load([
            'user:id,email',
            'items.proxyDelivery',
            'paymentTransactions' => fn ($query) => $query->latest(),
        ]);

        return view('admin.orders.show', compact('order'));
    }
}
