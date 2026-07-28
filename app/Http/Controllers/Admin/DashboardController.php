<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                'users' => User::query()->count(),
                'activeProducts' => Product::query()->active()->count(),
                'orders' => Order::query()->count(),
                'paidOrders' => Order::query()
                    ->where('payment_status', PaymentTransaction::STATUS_PAID)
                    ->count(),
                'processingOrders' => Order::query()
                    ->where('order_status', Order::STATUS_PROCESSING)
                    ->count(),
                'completedOrders' => Order::query()
                    ->where('order_status', Order::STATUS_COMPLETED)
                    ->count(),
                'problemOrders' => Order::query()
                    ->whereIn('order_status', [Order::STATUS_FAILED, Order::STATUS_CANCELLED])
                    ->count(),
            ],
        ]);
    }
}
