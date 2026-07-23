<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportLineApproval;
use App\Services\ExpenseApprovalRoutingService;
use App\Support\ExpenseApprovalHistoryBuilder;
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
            $reports = ExpenseReportLineApproval::query()
                ->with('expenseReportLine.expenseReport.employee')
                ->where('approver_id', Auth::id())
                ->where('status', $status)
                ->get()
                ->groupBy(fn (ExpenseReportLineApproval $a) => $a->expenseReportLine->expenseReport->id)
                ->map(fn ($group) => [
                    'expense_report' => $group->first()->expenseReportLine->expenseReport,
                    'pending_line_count' => $group->count(),
                ])
                ->values();
        } else {
            $userId = Auth::id();

            $reports = $expenseApprovalRoutingService->reportsForApprover($userId)
                ->with('employee', 'lines.lineApprovals.approvalMatrixLevel')
                ->get()
                ->map(fn (ExpenseReport $expenseReport) => [
                    'expense_report' => $expenseReport,
                    'pending_line_count' => $expenseReport->lines
                        ->filter(fn ($line) => optional($expenseApprovalRoutingService->currentLineApproval($line))->approver_id === $userId)
                        ->count(),
                ])
                ->values();
        }

        $paginated = new LengthAwarePaginator(
            $reports->forPage($page, $perPage)->values(),
            $reports->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Expense/Approval/Index', [
            'reports' => $paginated,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(ExpenseReport $expenseReport, ExpenseApprovalRoutingService $expenseApprovalRoutingService, ExpenseApprovalHistoryBuilder $historyBuilder): Response
    {
        $expenseReport->load(
            'employee.user',
            'lines.expenseCategory',
            'lines.project',
            'lines.media',
            'lines.lineApprovals.approver',
            'lines.lineApprovals.approvalMatrixLevel'
        );

        $isApproverOnReport = $expenseReport->lines
            ->flatMap(fn ($line) => $line->lineApprovals)
            ->contains(fn (ExpenseReportLineApproval $a) => $a->approver_id === Auth::id());

        abort_unless($isApproverOnReport, 403);

        $myTurnLineIds = $expenseReport->lines
            ->filter(function ($line) use ($expenseApprovalRoutingService) {
                $current = $expenseApprovalRoutingService->currentLineApproval($line);

                return $current && $current->approver_id === Auth::id();
            })
            ->pluck('id')
            ->values();

        return Inertia::render('Expense/Approval/Show', [
            'expenseReport' => $expenseReport,
            'myTurnLineIds' => $myTurnLineIds,
            'events' => $historyBuilder->build($expenseReport),
        ]);
    }

    public function approveLines(Request $request, ExpenseReport $expenseReport, ExpenseApprovalRoutingService $expenseApprovalRoutingService): RedirectResponse
    {
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'integer', 'exists:expense_report_lines,id'],
            'lines.*.remarks' => ['nullable', 'string'],
        ]);

        $lineIds = array_column($validated['lines'], 'line_id');
        $remarksByLineId = array_column($validated['lines'], 'remarks', 'line_id');

        $expenseApprovalRoutingService->bulkApprove($expenseReport, $lineIds, $remarksByLineId, Auth::id());

        return back()->with('success', 'Selected items approved.');
    }

    public function rejectLines(Request $request, ExpenseReport $expenseReport, ExpenseApprovalRoutingService $expenseApprovalRoutingService): RedirectResponse
    {
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'integer', 'exists:expense_report_lines,id'],
            'lines.*.remarks' => ['nullable', 'string'],
            'bulk_remarks' => ['nullable', 'string'],
        ]);

        $lineIds = array_column($validated['lines'], 'line_id');
        $remarksByLineId = array_column($validated['lines'], 'remarks', 'line_id');
        $bulkRemarks = $validated['bulk_remarks'] ?? null;

        $missingRemarks = collect($validated['lines'])
            ->filter(fn ($line, $index) => empty($line['remarks']) && empty($bulkRemarks))
            ->keys();

        if ($missingRemarks->isNotEmpty()) {
            $errors = $missingRemarks->mapWithKeys(fn ($index) => ["lines.{$index}.remarks" => 'A remark is required for each rejected item.']);

            return back()->withErrors($errors->all())->withInput();
        }

        $expenseApprovalRoutingService->bulkReject($expenseReport, $lineIds, $remarksByLineId, $bulkRemarks, Auth::id());

        return redirect()->route('expense.approvals.index')->with('success', 'Selected items rejected.');
    }
}
