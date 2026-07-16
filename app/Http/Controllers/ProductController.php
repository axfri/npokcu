<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product->load([
            'category',
            'durationOptions' => fn (HasMany $query) => $query->active()->ordered(),
        ]);

        abort_unless($product->is_active && $product->category?->is_active, 404);

        return view('products.show', compact('product'));
    }
}
