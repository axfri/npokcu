<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->withCount('products')
                ->ordered()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create($request->validated());

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Категория создана.');
    }

    public function edit(Category $category): View
    {
        $category->loadCount('products');

        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Категория сохранена.');
    }

    public function toggle(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with(
            'success',
            $category->is_active ? 'Категория включена.' : 'Категория отключена.',
        );
    }

    public function destroy(Category $category): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($category): bool {
            $lockedCategory = Category::query()
                ->lockForUpdate()
                ->findOrFail($category->getKey());

            if ($lockedCategory->products()->exists()) {
                return false;
            }

            return (bool) $lockedCategory->delete();
        });

        if (! $deleted) {
            return back()->with(
                'error',
                'Категорию с товарами удалить нельзя. Отключите её, чтобы скрыть из каталога.',
            );
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория удалена.');
    }
}
