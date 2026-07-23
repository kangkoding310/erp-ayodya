<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/Product/Index', [
            'products' => Product::query()
                ->with('category:id,name')
                ->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MasterData/Product/Create', [
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'accountType' => $this->accountTypeList(),
            'currency' => $this->currencyList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['coa_parent'] = ProductCategory::find($validated['product_category_id'])->coa;

        Product::create($validated);

        return redirect()->route('master-data.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('MasterData/Product/Edit', [
            'product' => $product,
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'accountType' => $this->accountTypeList(),
            'currency' => $this->currencyList(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['coa_parent'] = ProductCategory::find($validated['product_category_id'])->coa;

        $product->update($validated);

        return redirect()->route('master-data.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function accountTypeList()
    {
        return AccountType::orderBy('id')->get(['id', 'name']);
    }

    private function currencyList()
    {
        return Currency::orderBy('id')->get(['id', 'name']);
    }

    private function validated(Request $request): array
    {
        if ($request->has('coa')) {
            $request->merge(['coa' => (string) $request->input('coa')]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'user_price' => ['nullable', 'numeric', 'min:0'],
            'partner_price' => ['nullable', 'numeric', 'min:0'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'account_type' => ['nullable', 'integer', 'exists:account_types,id'],
            'coa' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'integer', 'exists:currencies,id'],
        ]);
    }
}
