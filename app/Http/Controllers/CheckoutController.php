<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\Orders\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function create(Request $request, Product $product): View
    {
        $product->load([
            'category',
            'durationOptions' => fn (HasMany $query) => $query->active()->ordered(),
        ]);

        abort_unless($product->is_active && $product->category?->is_active, 404);

        $checkoutToken = $this->issueCheckoutToken($request, $product);

        return view('checkout.create', compact('product', 'checkoutToken'));
    }

    public function store(
        CheckoutRequest $request,
        Product $product,
        CheckoutService $checkoutService,
    ): RedirectResponse {
        $validated = $request->validated();
        $guestCheckout = $request->user() === null;
        $order = $checkoutService->checkout(
            $product,
            (int) $validated['duration_option_id'],
            $validated['email'],
            $request->user(),
            $request->checkoutTokenHash(),
        );

        return redirect()->to($this->successUrl($order, $guestCheckout));
    }

    private function issueCheckoutToken(Request $request, Product $product): string
    {
        $checkoutToken = Str::random(64);
        $tokenHash = hash('sha256', $checkoutToken);
        $tokens = array_filter(
            $request->session()->get('checkout_tokens', []),
            fn (array $context): bool => (int) ($context['issued_at'] ?? 0)
                >= now()->subHours(2)->getTimestamp()
        );
        $tokens = array_slice($tokens, -9, null, true);
        $tokens[$tokenHash] = [
            'product_id' => $product->getKey(),
            'issued_at' => now()->getTimestamp(),
        ];

        $request->session()->put('checkout_tokens', $tokens);

        return $checkoutToken;
    }

    private function successUrl(Order $order, bool $guestCheckout): string
    {
        if (! $guestCheckout) {
            return route('orders.success', $order);
        }

        return URL::temporarySignedRoute(
            'orders.success',
            now()->addHour(),
            ['order' => $order]
        );
    }
}
