<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        $users = User::query()
            ->withCount('orders')
            ->when(
                $filters['email'] ?? null,
                fn ($query, $email) => $query->where('email', 'like', '%'.$email.'%'),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function show(User $user): View
    {
        $user->load([
            'orders' => fn ($query) => $query
                ->with(['items.proxyDelivery'])
                ->latest(),
        ]);

        $purchases = $user->orders
            ->flatMap(function ($order) {
                return $order->items->map(function (OrderItem $item) use ($order): OrderItem {
                    $item->setRelation('order', $order);
                    $item->proxyDelivery?->setRelation('order', $order);
                    $item->proxyDelivery?->setRelation('orderItem', $item);

                    return $item;
                });
            })
            ->values();

        return view('admin.users.show', [
            'user' => $user,
            'activePurchases' => $purchases
                ->filter(fn (OrderItem $item): bool => $item->isPurchaseActive())
                ->values(),
            'purchaseHistory' => $purchases
                ->reject(fn (OrderItem $item): bool => $item->isPurchaseActive())
                ->values(),
        ]);
    }
}
