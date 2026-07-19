<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\PurchaseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $purchaseTypes = PurchaseType::query()
                ->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        
        return Inertia::render('MasterData/PurchaseType/Index', [
            'purchaseTypes' => $purchaseTypes,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:purchase_types,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        PurchaseType::create($validated);

        return back()->with('success', 'Purchase type created.');
    }

    public function update(Request $request, PurchaseType $purchase_type): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:purchase_types,code,'.$purchase_type->id],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $purchase_type->update($validated);

        return back()->with('success', 'Purchase type updated.');
    }

    public function destroy(PurchaseType $purchase_type): RedirectResponse
    {
        $purchase_type->delete();

        return back()->with('success', 'Purchase type deleted.');
    }
}
