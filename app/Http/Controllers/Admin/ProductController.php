<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::query()
                ->with('category:id,name,slug')
                ->withCount('durationOptions')
                ->ordered()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'categories' => Category::query()->ordered()->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create($request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Товар создан. Теперь можно добавить варианты срока.');
    }

    public function edit(Product $product): View
    {
        $product->load(['durationOptions' => fn ($query) => $query->ordered()]);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::query()->ordered()->get(['id', 'name', 'is_active']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Товар сохранён.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with(
            'success',
            $product->is_active ? 'Товар включён.' : 'Товар отключён.',
        );
    }
}
