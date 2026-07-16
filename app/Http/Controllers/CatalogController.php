<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->with([
                'products' => fn (HasMany $query) => $query->active()->ordered(),
            ])
            ->get();

        return view('catalog.index', [
            'categories' => $categories,
            'productCount' => $categories->sum(
                fn (Category $category): int => $category->products->count()
            ),
        ]);
    }

    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'products' => fn (HasMany $query) => $query->active()->ordered(),
        ]);

        return view('catalog.category', compact('category'));
    }
}
