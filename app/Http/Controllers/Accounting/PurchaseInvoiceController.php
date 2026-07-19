<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRfq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Accounting/PurchaseInvoice/Index', [
            'invoices' => PurchaseInvoice::query()
                ->with('purchaseRfq.purchaseRequest:id,code')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/PurchaseInvoice/Create', [
            'purchaseRfqs' => PurchaseRfq::query()
                ->with('purchaseRequest:id,code,total_amount')
                ->where('status', 'sent')
                ->doesntHave('invoices')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_rfq_id' => ['required', 'exists:purchase_rfqs,id'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = PurchaseInvoice::create($validated);

        return redirect()->route('accounting.purchase-invoices.index')->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(PurchaseInvoice $purchase_invoice): Response
    {
        return Inertia::render('Accounting/PurchaseInvoice/Show', [
            'invoice' => $purchase_invoice->load('purchaseRfq.purchaseRequest', 'payments.bank'),
        ]);
    }
}
