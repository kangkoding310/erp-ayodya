<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PurchaseRequestLineController extends Controller
{
    public function store(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        $validated = $this->validated($request);

        $purchaseRequest->lines()->create([
            ...$validated,
            'subtotal' => $validated['qty'] * $validated['price_estimate'],
        ]);

        return back()->with('success', 'Line added.');
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest, PurchaseRequestLine $line): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        $validated = $this->validated($request);

        $line->update([
            ...$validated,
            'subtotal' => $validated['qty'] * $validated['price_estimate'],
        ]);

        return back()->with('success', 'Line updated.');
    }

    public function destroy(PurchaseRequest $purchaseRequest, PurchaseRequestLine $line): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        $line->delete();

        return back()->with('success', 'Line removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'description' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'price_estimate' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
