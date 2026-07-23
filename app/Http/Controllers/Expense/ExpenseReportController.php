<?php

namespace App\Http\Controllers\Expense;

use App\Enums\ApprovalStatus;
use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovalMatrixLevel;
use App\Models\Employee;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportLineApproval;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Services\ExpenseApprovalRoutingService;
use App\Services\ExpenseReportService;
use App\Support\ExpenseApprovalHistoryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseReportController extends Controller
{
    public function index(Request $request, ExpenseApprovalRoutingService $expenseApprovalRoutingService): Response
    {
        $userId = Auth::id();

        $sortable = ['code', 'employee', 'total_expense', 'status', 'created_at'];
        $sort = in_array($request->string('sort')->value(), $sortable, true) ? $request->string('sort')->value() : 'created_at';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        // Reports are private to the employee who created them; approvers track
        // their involvement via the separate "expense.approvals" pages instead.
        $baseQuery = ExpenseReport::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $userId));

        $totalRange = [
            'min' => 0,
            'max' => (int) ceil((clone $baseQuery)->max('total_expense') ?? 0),
        ];

        $expenseReports = (clone $baseQuery)
            ->with('employee:id,name', 'lines')
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('employee_id')->value(), fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($request->string('search')->value(), fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('code', 'ilike', "%{$search}%")
                    ->orWhereHas('employee', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));
            }))
            ->when($request->filled('min_total'), fn ($q) => $q->where('total_expense', '>=', $request->float('min_total')))
            ->when($request->filled('max_total'), fn ($q) => $q->where('total_expense', '<=', $request->float('max_total')))
            ->when(
                $sort === 'employee',
                fn ($q) => $q->join('employees', 'employees.id', '=', 'expense_reports.employee_id')
                    ->orderBy('employees.name', $direction)
                    ->select('expense_reports.*'),
                fn ($q) => $q->orderBy($sort, $direction)
            )
            ->paginate(15)
            ->withQueryString();

        $expenseReports->getCollection()->each(function (ExpenseReport $report) use ($expenseApprovalRoutingService) {
            $report->current_approver_name = $report->status === ExpenseStatus::InApproval
                ? $expenseApprovalRoutingService->currentApproverName($report)
                : null;
        });

        return Inertia::render('Expense/Index', [
            'expenseReports' => $expenseReports,
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'statuses' => collect(ExpenseStatus::cases())->map(fn ($status) => [
                'id' => $status->value,
                'text' => $status->label(),
            ])->values(),
            'totalRange' => $totalRange,
            'filters' => $request->only('status', 'employee_id', 'search', 'min_total', 'max_total', 'sort', 'direction'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expense/Create', $this->formOptions());
    }

    public function store(Request $request, ExpenseReportService $expenseReportService): RedirectResponse
    {
        $validated = $this->validated($request);

        $expenseReport = DB::transaction(function () use ($validated, $expenseReportService) {
            $expenseReport = ExpenseReport::create([
                'code' => $expenseReportService->generateCode(),
                'employee_id' => $validated['employee_id'],
                'summary' => $validated['summary'] ?? null,
            ]);

            $this->syncLines($expenseReport, $validated['lines']);

            return $expenseReport;
        });

        return redirect()->route('expense.reports.show', $expenseReport)->with('success', 'Expense report created.');
    }

    public function show(ExpenseReport $expenseReport, ExpenseApprovalHistoryBuilder $historyBuilder): Response
    {
        $expenseReport->load(
            'employee.division',
            'employee.user',
            'lines.expenseCategory',
            'lines.project',
            'lines.media',
            'lines.lineApprovals.approver',
            'lines.lineApprovals.approvalMatrixLevel'
        );

        return Inertia::render('Expense/Show', [
            'expenseReport' => $expenseReport,
            'events' => $historyBuilder->build($expenseReport),
            'expenseCategories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(ExpenseReport $expenseReport): Response
    {
        return Inertia::render('Expense/Edit', [
            'expenseReport' => $expenseReport->load('lines'),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, ExpenseReport $expenseReport): RedirectResponse
    {
        abort_unless($expenseReport->employee->user_id === Auth::id(), 403);
        abort_unless($expenseReport->status === ExpenseStatus::Draft, 409);

        $expenseReport->loadMissing('lines.media');

        $validated = $this->validated($request, $expenseReport);

        DB::transaction(function () use ($validated, $expenseReport) {
            $expenseReport->update([
                'employee_id' => $validated['employee_id'],
                'summary' => $validated['summary'] ?? null,
            ]);

            $this->syncLinesForUpdate($expenseReport, $validated['lines']);
        });

        return redirect()->route('expense.reports.show', $expenseReport)->with('success', 'Expense report updated.');
    }

    public function destroy(ExpenseReport $expenseReport): RedirectResponse
    {
        abort_unless($expenseReport->employee->user_id === Auth::id(), 403);

        $expenseReport->delete();

        return redirect()->route('expense.reports.index')->with('success', 'Expense report deleted.');
    }

    public function submit(ExpenseReport $expenseReport, ExpenseApprovalRoutingService $expenseApprovalRoutingService): RedirectResponse
    {
        abort_unless($expenseReport->employee->user_id === Auth::id(), 403);
        abort_unless($expenseReport->status === ExpenseStatus::Draft, 409);

        $expenseApprovalRoutingService->submit($expenseReport);

        return back()->with('success', 'Expense report submitted.');
    }

    public function cancel(ExpenseReport $expenseReport): RedirectResponse
    {
        abort_unless($expenseReport->employee->user_id === Auth::id(), 403);
        abort_unless(
            \in_array($expenseReport->status, [
                ExpenseStatus::Draft,
                ExpenseStatus::Submitted,
                ExpenseStatus::InApproval,
                ExpenseStatus::NeedsRevision,
            ], true),
            409
        );

        DB::transaction(function () use ($expenseReport) {
            $lineIds = $expenseReport->lines()->pluck('id');

            ExpenseReportLineApproval::whereIn('expense_report_line_id', $lineIds)
                ->whereIn('status', [ApprovalStatus::Pending, ApprovalStatus::Skipped])
                ->delete();

            $expenseReport->lines()->update(['status' => ApprovalStatus::Cancelled]);

            $expenseReport->update(['status' => ExpenseStatus::Cancelled]);
        });

        return back()->with('success', 'Expense report cancelled.');
    }

    public function resubmit(Request $request, ExpenseReport $expenseReport, ExpenseApprovalRoutingService $expenseApprovalRoutingService): RedirectResponse
    {
        abort_unless($expenseReport->employee->user_id === Auth::id(), 403);
        abort_unless($expenseReport->status === ExpenseStatus::NeedsRevision, 409);

        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer', 'exists:expense_report_lines,id'],
            'lines.*.expense_date' => ['required', 'date'],
            'lines.*.expense_category_id' => ['required', 'exists:product_categories,id'],
            'lines.*.project_id' => ['nullable', 'exists:projects,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.total' => ['required', 'numeric', 'min:0'],
            'lines.*.attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $lineIds = collect($validated['lines'])->pluck('id')->all();

        DB::transaction(function () use ($expenseReport, $validated) {
            foreach ($validated['lines'] as $lineData) {
                $line = $expenseReport->lines()
                    ->where('id', $lineData['id'])
                    ->where('status', ApprovalStatus::Rejected)
                    ->firstOrFail();

                $attachment = $lineData['attachment'] ?? null;
                unset($lineData['id'], $lineData['attachment']);

                $line->update($lineData);

                if ($attachment) {
                    $line->addMedia($attachment)->toMediaCollection('attachments');
                }
            }
        });

        $expenseApprovalRoutingService->resubmitAfterRevision($expenseReport, $lineIds);

        return redirect()->route('expense.reports.show', $expenseReport)->with('success', 'Expense report resubmitted for approval.');
    }

    public function sendToAccounting(ExpenseReport $expenseReport, ExpenseReportService $expenseReportService): RedirectResponse
    {
        $isLevelOneApprover = ApprovalMatrixLevel::query()
            ->where('level', 1)
            ->whereHas('approvalMatrix.purchaseType', fn ($q) => $q->where('name', 'Expense'))
            ->where('approver_id', Auth::id())
            ->exists();

        abort_unless($isLevelOneApprover, 403);

        $expenseReportService->sendToAccounting($expenseReport);

        return back()->with('success', 'Expense report sent to accounting.');
    }

    private function syncLines(ExpenseReport $expenseReport, array $lines): void
    {
        foreach ($lines as $line) {
            $attachment = $line['attachment'] ?? null;
            unset($line['attachment']);

            $expenseReportLine = $expenseReport->lines()->create($line);

            if ($attachment) {
                $expenseReportLine->addMedia($attachment)->toMediaCollection('attachments');
            }
        }
    }

    /**
     * Like syncLines(), but diffs against the report's existing lines instead of wiping
     * them all: lines the requester removed are deleted, lines they kept are updated in
     * place (so an untouched line keeps its existing attachment), and only lines without
     * an id are newly created.
     */
    private function syncLinesForUpdate(ExpenseReport $expenseReport, array $lines): void
    {
        $existingIds = $expenseReport->lines->pluck('id')->all();
        $keptIds = collect($lines)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $expenseReport->lines()->whereIn('id', array_diff($existingIds, $keptIds))->delete();

        foreach ($lines as $line) {
            $attachment = $line['attachment'] ?? null;
            $lineId = isset($line['id']) ? (int) $line['id'] : null;
            unset($line['attachment'], $line['id']);

            if ($lineId && in_array($lineId, $existingIds, true)) {
                $expenseReportLine = $expenseReport->lines()->whereKey($lineId)->firstOrFail();
                $expenseReportLine->update($line);
            } else {
                $expenseReportLine = $expenseReport->lines()->create($line);
            }

            if ($attachment) {
                $expenseReportLine->clearMediaCollection('attachments');
                $expenseReportLine->addMedia($attachment)->toMediaCollection('attachments');
            }
        }
    }

    private function validated(Request $request, ?ExpenseReport $expenseReport = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'summary' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'integer'],
            'lines.*.expense_date' => ['required', 'date'],
            'lines.*.expense_category_id' => ['required', 'exists:product_categories,id'],
            'lines.*.project_id' => ['required', 'exists:projects,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.total' => ['required', 'numeric', 'gt:0'],
            'lines.*.attachment' => Rule::forEach(function ($value, string $attribute) use ($request, $expenseReport) {
                $rules = ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'];

                if (! preg_match('/^lines\.(\d+)\.attachment$/', $attribute, $matches)) {
                    return array_merge(['required'], $rules);
                }

                $lineId = $request->input("lines.{$matches[1]}.id");
                $hasExistingAttachment = $expenseReport && $lineId
                    && $expenseReport->lines->firstWhere('id', (int) $lineId)?->getFirstMedia('attachments');

                return array_merge([$hasExistingAttachment ? 'nullable' : 'required'], $rules);
            }),
        ], [
            'lines.*.expense_date.required' => 'Date is required.',
            'lines.*.expense_category_id.required' => 'Category is required.',
            'lines.*.project_id.required' => 'Project is required.',
            'lines.*.total.required' => 'Total is required.',
            'lines.*.total.gt' => 'Total is required.',
            'lines.*.attachment.required' => 'Attachment is required.',
        ]);
    }

    private function formOptions(): array
    {
        return [
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'expenseCategories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ];
    }
}
