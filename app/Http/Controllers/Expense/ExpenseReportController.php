<?php

namespace App\Http\Controllers\Expense;

use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovalMatrixLevel;
use App\Models\Employee;
use App\Models\ExpenseReport;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Services\ExpenseApprovalRoutingService;
use App\Services\ExpenseReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseReportController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = Auth::id();

        // Approvers configured on the "Expense" approval matrix can track every
        // expense report, not just their own; everyone else only sees their own.
        $isApprover = ApprovalMatrixLevel::query()
            ->where('approver_id', $userId)
            ->whereHas('approvalMatrix.purchaseType', fn ($q) => $q->where('name', 'Expense'))
            ->exists();

        $sortable = ['code', 'employee', 'total_expense', 'status', 'created_at'];
        $sort = in_array($request->string('sort')->value(), $sortable, true) ? $request->string('sort')->value() : 'created_at';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        $baseQuery = ExpenseReport::query()
            ->when(
                ! $isApprover,
                fn ($q) => $q->whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            );

        $totalRange = [
            'min' => 0,
            'max' => (int) ceil((clone $baseQuery)->max('total_expense') ?? 0),
        ];

        $expenseReports = (clone $baseQuery)
            ->with('employee:id,name')
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

    public function show(ExpenseReport $expenseReport): Response
    {
        return Inertia::render('Expense/Show', [
            'expenseReport' => $expenseReport->load(
                'employee.division',
                'lines.expenseCategory',
                'lines.project',
                'lines.media',
                'approvals.approver',
                'approvals.approvalMatrixLevel'
            ),
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
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated, $expenseReport) {
            $expenseReport->update([
                'employee_id' => $validated['employee_id'],
                'summary' => $validated['summary'] ?? null,
            ]);

            $expenseReport->lines()->delete();
            $this->syncLines($expenseReport, $validated['lines']);
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
            \in_array($expenseReport->status, [ExpenseStatus::Draft, ExpenseStatus::Submitted, ExpenseStatus::InApproval], true),
            409
        );

        DB::transaction(function () use ($expenseReport) {
            $expenseReport->approvals()->where('status', 'pending')->delete();
            $expenseReport->update(['status' => ExpenseStatus::Cancelled]);
        });

        return back()->with('success', 'Expense report cancelled.');
    }

    public function sendToAccounting(ExpenseReport $expenseReport, ExpenseReportService $expenseReportService): RedirectResponse
    {
        $levelOneApproval = $expenseReport->approvals()
            ->whereHas('approvalMatrixLevel', fn ($q) => $q->where('level', 1))
            ->first();

        abort_unless($levelOneApproval && $levelOneApproval->approver_id === Auth::id(), 403);

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

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'summary' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.expense_date' => ['required', 'date'],
            'lines.*.expense_category_id' => ['required', 'exists:product_categories,id'],
            'lines.*.project_id' => ['nullable', 'exists:projects,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.total' => ['required', 'numeric', 'min:0'],
            'lines.*.attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
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
