<?php

namespace App\Http\Controllers\Expense;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\ExpenseReportApproval;
use App\Services\ExpenseApprovalRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseApprovalController extends Controller
{
    public function index(Request $request, ExpenseApprovalRoutingService $expenseApprovalRoutingService): Response
    {
        $status = $request->string('status')->value();
        $page = $request->integer('page', 1);
        $perPage = 15;

        if ($status) {
            $approvals = ExpenseReportApproval::query()
                ->with('expenseReport.employee')
                ->where('approver_id', Auth::id())
                ->where('status', $status)
                ->latest()
                ->paginate($perPage)
                ->withQueryString();
        } else {
            // Pending approvals only appear once it's actually this approver's
            // turn in the level sequence, so filtering happens in PHP rather
            // than the query.
            $myTurn = ExpenseReportApproval::query()
                ->with('expenseReport.employee', 'expenseReport.approvals.approvalMatrixLevel')
                ->where('approver_id', Auth::id())
                ->where('status', ApprovalStatus::Pending)
                ->latest()
                ->get()
                ->filter(fn (ExpenseReportApproval $approval) => $expenseApprovalRoutingService->isCurrentTurn($approval))
                ->values();

            $approvals = new LengthAwarePaginator(
                $myTurn->forPage($page, $perPage)->values(),
                $myTurn->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return Inertia::render('Expense/Approval/Index', [
            'approvals' => $approvals,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(ExpenseReportApproval $approval, ExpenseApprovalRoutingService $expenseApprovalRoutingService): Response
    {
        abort_unless($approval->approver_id === Auth::id(), 403);
        abort_unless(
            $approval->status !== ApprovalStatus::Pending || $expenseApprovalRoutingService->isCurrentTurn($approval),
            409
        );

        return Inertia::render('Expense/Approval/Show', [
            'approval' => $approval->load(
                'expenseReport.employee',
                'expenseReport.lines.expenseCategory',
                'expenseReport.approvals.approver',
                'expenseReport.approvals.approvalMatrixLevel'
            ),
        ]);
    }

    public function approve(Request $request, ExpenseReportApproval $approval, ExpenseApprovalRoutingService $expenseApprovalRoutingService): RedirectResponse
    {
        abort_unless($approval->approver_id === Auth::id(), 403);
        abort_unless($expenseApprovalRoutingService->isCurrentTurn($approval), 409);

        $validated = $request->validate(['remarks' => ['nullable', 'string']]);

        $expenseApprovalRoutingService->approve($approval, $validated['remarks'] ?? null);

        return redirect()->route('expense.approvals.index')->with('success', 'Expense report approved.');
    }

    public function reject(Request $request, ExpenseReportApproval $approval, ExpenseApprovalRoutingService $expenseApprovalRoutingService): RedirectResponse
    {
        abort_unless($approval->approver_id === Auth::id(), 403);
        abort_unless($expenseApprovalRoutingService->isCurrentTurn($approval), 409);

        $validated = $request->validate(['remarks' => ['required', 'string']]);

        $expenseApprovalRoutingService->reject($approval, $validated['remarks']);

        return redirect()->route('expense.approvals.index')->with('success', 'Expense report rejected.');
    }
}
