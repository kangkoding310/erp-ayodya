<?php

namespace App\Http\Controllers\Expense;

use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovalMatrixLevel;
use App\Models\Employee;
use App\Models\ExpenseReport;
use App\Models\ProductCategory;
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

        return Inertia::render('Expense/Index', [
            'expenseReports' => ExpenseReport::query()
                ->with('employee:id,name')
                ->when(
                    ! $isApprover,
                    fn ($q) => $q->whereHas('employee', fn ($q) => $q->where('user_id', $userId))
                )
                ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('status'),
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
            $expenseReport->lines()->create($line);
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
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.total' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'expenseCategories' => ProductCategory::orderBy('name')->get(['id', 'name']),
        ];
    }
}
