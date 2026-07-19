<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseApproval;
use App\Services\ApprovalRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Purchase/Approval/Index', [
            'approvals' => PurchaseApproval::query()
                ->with('purchaseRequest.employee', 'purchaseRequest.purchaseType')
                ->where('approver_id', Auth::id())
                ->when(
                    $request->string('status')->value(),
                    fn ($q, $status) => $q->where('status', $status),
                    fn ($q) => $q->where('status', 'pending')
                )
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function show(PurchaseApproval $approval): Response
    {
        abort_unless($approval->approver_id === Auth::id(), 403);

        return Inertia::render('Purchase/Approval/Show', [
            'approval' => $approval->load(
                'purchaseRequest.purchaseType',
                'purchaseRequest.employee',
                'purchaseRequest.division',
                'purchaseRequest.lines.product',
                'purchaseRequest.approvals.approver',
                'purchaseRequest.approvals.approvalMatrixLevel'
            ),
        ]);
    }

    public function approve(Request $request, PurchaseApproval $approval, ApprovalRoutingService $approvalRoutingService): RedirectResponse
    {
        abort_unless($approval->approver_id === Auth::id(), 403);

        $validated = $request->validate(['remarks' => ['nullable', 'string']]);

        $approvalRoutingService->approve($approval, $validated['remarks'] ?? null);

        return redirect()->route('purchase.approvals.index')->with('success', 'Purchase request approved.');
    }

    public function reject(Request $request, PurchaseApproval $approval, ApprovalRoutingService $approvalRoutingService): RedirectResponse
    {
        abort_unless($approval->approver_id === Auth::id(), 403);

        $validated = $request->validate(['remarks' => ['required', 'string']]);

        $approvalRoutingService->reject($approval, $validated['remarks']);

        return redirect()->route('purchase.approvals.index')->with('success', 'Purchase request rejected.');
    }
}
