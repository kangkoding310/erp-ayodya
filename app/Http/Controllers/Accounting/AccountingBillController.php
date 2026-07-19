<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingBill;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountingBillController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Accounting/Bills/Index', [
            'bills' => AccountingBill::query()
                ->with('source')
                ->when($request->string('source_type')->value(), fn ($q, $type) => $q->where('source_type', $type))
                ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('source_type', 'status'),
        ]);
    }

    public function show(AccountingBill $bill): Response
    {
        return Inertia::render('Accounting/Bills/Show', [
            'bill' => $bill->load('source'),
        ]);
    }
}
