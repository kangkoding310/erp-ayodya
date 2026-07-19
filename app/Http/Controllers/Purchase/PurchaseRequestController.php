<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequestRequest;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseType;
use App\Services\ApprovalRoutingService;
use App\Services\PurchaseRequestNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Purchase/Request/Index', [
            'purchaseRequests' => PurchaseRequest::query()
                ->with('purchaseType:id,name', 'employee:id,name')
                ->where('requested_by', Auth::id())
                ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchase/Request/Create', $this->formOptions());
    }

    public function store(StorePurchaseRequestRequest $request, PurchaseRequestNumberGenerator $numberGenerator): RedirectResponse
    {
        $validated = $request->validated();

        $purchaseRequest = DB::transaction(function () use ($validated, $numberGenerator) {
            $purchaseRequest = PurchaseRequest::create([
                'code' => $numberGenerator->generate(),
                'purchase_type_id' => $validated['purchase_type_id'],
                'project_name' => $validated['project_name'] ?? null,
                'currency' => $validated['currency'],
                'employee_id' => $validated['employee_id'],
                'expected_date' => $validated['expected_date'] ?? null,
                'requested_by' => Auth::id(),
                'division_id' => $validated['division_id'],
            ]);

            $this->syncLines($purchaseRequest, $validated['lines']);

            return $purchaseRequest;
        });

        return redirect()->route('purchase.requests.show', $purchaseRequest)->with('success', 'Purchase request created.');
    }

    public function show(PurchaseRequest $purchaseRequest): Response
    {
        $this->authorize('view', $purchaseRequest);

        return Inertia::render('Purchase/Request/Show', [
            'purchaseRequest' => $purchaseRequest->load(
                'purchaseType',
                'employee.division',
                'requestedBy',
                'division',
                'lines.product',
                'messages.user',
                'approvals.approver',
                'approvals.approvalMatrixLevel',
                'rfq'
            ),
        ]);
    }

    public function edit(PurchaseRequest $purchaseRequest): Response
    {
        $this->authorize('update', $purchaseRequest);

        return Inertia::render('Purchase/Request/Edit', [
            'purchaseRequest' => $purchaseRequest->load('lines'),
            ...$this->formOptions(),
        ]);
    }

    public function update(StorePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $purchaseRequest) {
            $purchaseRequest->update([
                'purchase_type_id' => $validated['purchase_type_id'],
                'project_name' => $validated['project_name'] ?? null,
                'currency' => $validated['currency'],
                'employee_id' => $validated['employee_id'],
                'expected_date' => $validated['expected_date'] ?? null,
                'division_id' => $validated['division_id'],
            ]);

            $purchaseRequest->lines()->delete();
            $this->syncLines($purchaseRequest, $validated['lines']);
        });

        return redirect()->route('purchase.requests.show', $purchaseRequest)->with('success', 'Purchase request updated.');
    }

    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('delete', $purchaseRequest);

        $purchaseRequest->delete();

        return redirect()->route('purchase.requests.index')->with('success', 'Purchase request deleted.');
    }

    public function submit(PurchaseRequest $purchaseRequest, ApprovalRoutingService $approvalRoutingService): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        $approvalRoutingService->submit($purchaseRequest);

        return back()->with('success', 'Purchase request submitted for approval.');
    }

    private function syncLines(PurchaseRequest $purchaseRequest, array $lines): void
    {
        foreach ($lines as $line) {
            $purchaseRequest->lines()->create([
                'product_id' => $line['product_id'],
                'description' => $line['description'] ?? null,
                'qty' => $line['qty'],
                'price_estimate' => $line['price_estimate'],
                'subtotal' => $line['qty'] * $line['price_estimate'],
            ]);
        }
    }

    private function formOptions(): array
    {
        return [
            'purchaseTypes' => PurchaseType::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name', 'price', 'tax_percentage']),
        ];
    }
}
