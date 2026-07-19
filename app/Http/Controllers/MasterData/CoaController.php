<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Coa;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/Coa/Index', [
            'coa' => Coa::query()
                ->with('product:id,name')
                ->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
                ->orderBy('code')
                ->paginate(15)
                ->withQueryString(),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coa,code'],
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['nullable', 'exists:products,id'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        Coa::create($validated);

        return back()->with('success', 'COA created.');
    }

    public function update(Request $request, Coa $coa): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coa,code,'.$coa->id],
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['nullable', 'exists:products,id'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        $coa->update($validated);

        return back()->with('success', 'COA updated.');
    }

    public function destroy(Coa $coa): RedirectResponse
    {
        $coa->delete();

        return back()->with('success', 'COA deleted.');
    }
}
