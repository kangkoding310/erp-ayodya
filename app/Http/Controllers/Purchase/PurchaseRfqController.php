<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRfq;
use App\Services\PurchaseToRfqService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRfqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Purchase/Rfq/Index', [
            'purchaseRequests' => PurchaseRequest::query()
                ->with('purchaseType:id,name', 'employee:id,name', 'rfq')
                ->whereIn('status', ['approved', 'in_rfq', 'sent_to_accounting'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(PurchaseRequest $purchaseRequest, PurchaseToRfqService $purchaseToRfqService): RedirectResponse
    {
        $purchaseToRfqService->moveToRfq($purchaseRequest);

        return back()->with('success', 'Purchase request moved to RFQ.');
    }

    public function sendToAccounting(PurchaseRfq $purchaseRfq, PurchaseToRfqService $purchaseToRfqService): RedirectResponse
    {
        $purchaseToRfqService->sendToAccounting($purchaseRfq);

        return back()->with('success', 'RFQ sent to accounting.');
    }
}
