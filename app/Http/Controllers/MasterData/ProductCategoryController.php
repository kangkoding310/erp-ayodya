<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/ProductCategory/Index', [
            'productCategories' => ProductCategory::query()
                ->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_categories,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        ProductCategory::create($validated);

        return back()->with('success', 'Product category created.');
    }

    public function update(Request $request, ProductCategory $product_category): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_categories,code,'.$product_category->id],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $product_category->update($validated);

        return back()->with('success', 'Product category updated.');
    }

    public function destroy(ProductCategory $product_category): RedirectResponse
    {
        $product_category->delete();

        return back()->with('success', 'Product category deleted.');
    }
}
