<?php

namespace App\Http\Controllers\Expense;

use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
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
        $userId = Auth::id();
        $status = $request->string('status')->value();

        // "status" here means the approver's own decision on lines (approved/rejected by
        // me), not the report's overall status — an empty value keeps the default "needs
        // my action now" listing (reportsForApprover), which has different semantics
        // (current-turn only) than a decision-status search over past line approvals.
        if ($status) {
            $rows = ExpenseReportLineApproval::query()
                ->with('expenseReportLine.expenseReport.employee', 'expenseReportLine.expenseReport.lines.lineApprovals.approver')
                ->where('approver_id', $userId)
                ->where('status', $status)
                ->get()
                ->groupBy(fn (ExpenseReportLineApproval $a) => $a->expenseReportLine->expenseReport->id)
                ->map(function ($group) use ($expenseApprovalRoutingService) {
                    $expenseReport = $group->first()->expenseReportLine->expenseReport;

                    return [
                        'expense_report' => $expenseReport,
                        'pending_line_count' => $group->count(),
                        'current_approver_name' => $expenseReport->status === ExpenseStatus::InApproval
                            ? $expenseApprovalRoutingService->currentApproverName($expenseReport)
                            : null,
                    ];
                })
                ->values();
        } else {
            $rows = $expenseApprovalRoutingService->reportsForApprover($userId)
                ->with('employee', 'lines.lineApprovals.approver', 'lines.lineApprovals.approvalMatrixLevel')
                ->get()
                ->map(fn (ExpenseReport $expenseReport) => [
                    'expense_report' => $expenseReport,
                    'pending_line_count' => $expenseReport->lines
                        ->filter(fn ($line) => optional($expenseApprovalRoutingService->currentLineApproval($line))->approver_id === $userId)
                        ->count(),
                    'current_approver_name' => $expenseReport->status === ExpenseStatus::InApproval
                        ? $expenseApprovalRoutingService->currentApproverName($expenseReport)
                        : null,
                ])
                ->values();
        }

        $totalRange = [
            'min' => 0,
            'max' => (int) ceil($rows->max(fn ($row) => (float) $row['expense_report']->total_expense) ?? 0),
        ];

        $search = $request->string('search')->value();
        $employeeId = $request->string('employee_id')->value();
        $minTotal = $request->filled('min_total') ? $request->float('min_total') : null;
        $maxTotal = $request->filled('max_total') ? $request->float('max_total') : null;

        $filtered = $rows
            ->when($search, fn ($rows) => $rows->filter(function ($row) use ($search) {
                $report = $row['expense_report'];

                return str_contains(strtolower($report->code), strtolower($search))
                    || str_contains(strtolower($report->employee?->name ?? ''), strtolower($search));
            }))
            ->when($employeeId, fn ($rows) => $rows->filter(
                fn ($row) => (string) $row['expense_report']->employee_id === $employeeId
            ))
            ->when($minTotal !== null, fn ($rows) => $rows->filter(
                fn ($row) => (float) $row['expense_report']->total_expense >= $minTotal
            ))
            ->when($maxTotal !== null, fn ($rows) => $rows->filter(
                fn ($row) => (float) $row['expense_report']->total_expense <= $maxTotal
            ));

        $sortable = ['code', 'employee', 'total_expense', 'status'];
        $sort = in_array($request->string('sort')->value(), $sortable, true) ? $request->string('sort')->value() : 'code';
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';

        $sorted = match ($sort) {
            'employee' => $filtered->sortBy(fn ($row) => strtolower($row['expense_report']->employee?->name ?? '')),
            'total_expense' => $filtered->sortBy(fn ($row) => (float) $row['expense_report']->total_expense),
            'status' => $filtered->sortBy(fn ($row) => $row['expense_report']->status->value),
            default => $filtered->sortBy(fn ($row) => $row['expense_report']->code),
        };

        if ($direction === 'desc') {
            $sorted = $sorted->reverse();
        }

        $sorted = $sorted->values();

        $page = $request->integer('page', 1);
        $perPage = 15;

        $paginated = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Expense/Approval/Index', [
            'reports' => $paginated,
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'statuses' => [
                ['id' => 'approved', 'text' => 'Approved by Me'],
                ['id' => 'rejected', 'text' => 'Rejected by Me'],
            ],
            'totalRange' => $totalRange,
            'filters' => $request->only('status', 'employee_id', 'search', 'min_total', 'max_total', 'sort', 'direction'),
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
