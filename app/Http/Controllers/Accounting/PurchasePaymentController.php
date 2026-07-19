<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchasePaymentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Accounting/PurchasePayment/Index', [
            'payments' => PurchasePayment::query()
                ->with('purchaseInvoice.purchaseRfq.purchaseRequest:id,code', 'bank')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/PurchasePayment/Create', [
            'purchaseInvoices' => PurchaseInvoice::with('purchaseRfq.purchaseRequest:id,code')->get(),
            'banks' => Bank::orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_invoice_id' => ['required', 'exists:purchase_invoices,id'],
            'bank_id' => ['required', 'exists:banks,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        PurchasePayment::create($validated);

        return redirect()->route('accounting.purchase-payments.index')->with('success', 'Payment recorded.');
    }

    public function show(PurchasePayment $purchase_payment): Response
    {
        return Inertia::render('Accounting/PurchasePayment/Show', [
            'payment' => $purchase_payment->load('purchaseInvoice.purchaseRfq.purchaseRequest', 'bank'),
        ]);
    }
}
