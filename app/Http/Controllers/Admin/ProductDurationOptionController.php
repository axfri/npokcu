<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDurationOptionRequest;
use App\Http\Requests\Admin\UpdateDurationOptionRequest;
use App\Models\Product;
use App\Models\ProductDurationOption;
use Illuminate\Http\RedirectResponse;

class ProductDurationOptionController extends Controller
{
    public function store(
        StoreDurationOptionRequest $request,
        Product $product,
    ): RedirectResponse {
        $product->durationOptions()->create($request->validated());

        return back()->with('success', 'Вариант срока добавлен.');
    }

    public function update(
        UpdateDurationOptionRequest $request,
        Product $product,
        ProductDurationOption $durationOption,
    ): RedirectResponse {
        $this->ensureBelongsToProduct($product, $durationOption);
        $durationOption->update($request->validated());

        return back()->with('success', 'Вариант срока сохранён.');
    }

    public function destroy(
        Product $product,
        ProductDurationOption $durationOption,
    ): RedirectResponse {
        $this->ensureBelongsToProduct($product, $durationOption);
        $durationOption->delete();

        return back()->with('success', 'Вариант срока удалён. История заказов не изменена.');
    }

    private function ensureBelongsToProduct(
        Product $product,
        ProductDurationOption $durationOption,
    ): void {
        abort_unless($durationOption->product_id === $product->getKey(), 404);
    }
}
