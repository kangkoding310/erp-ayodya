<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestMessageController extends Controller
{
    public function store(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('view', $purchaseRequest);

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $purchaseRequest->messages()->create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
        ]);

        return back()->with('success', 'Message sent.');
    }
}
