<?php

namespace App\Http\Controllers\Expense;

use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\ExpenseReport;
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
        return Inertia::render('Expense/Index', [
            'expenseReports' => ExpenseReport::query()
                ->with('employee:id,name')
                ->whereHas('employee', fn ($q) => $q->where('user_id', Auth::id()))
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
            'expenseReport' => $expenseReport->load('employee.division', 'lines.expenseCategory'),
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
        $expenseReport->delete();

        return redirect()->route('expense.reports.index')->with('success', 'Expense report deleted.');
    }

    public function submit(ExpenseReport $expenseReport): RedirectResponse
    {
        $expenseReport->update(['status' => ExpenseStatus::Submitted]);

        return back()->with('success', 'Expense report submitted.');
    }

    public function approve(ExpenseReport $expenseReport): RedirectResponse
    {
        $expenseReport->update(['status' => ExpenseStatus::Approved]);

        return back()->with('success', 'Expense report approved.');
    }

    public function reject(ExpenseReport $expenseReport): RedirectResponse
    {
        $expenseReport->update(['status' => ExpenseStatus::Rejected]);

        return back()->with('success', 'Expense report rejected.');
    }

    public function sendToAccounting(ExpenseReport $expenseReport, ExpenseReportService $expenseReportService): RedirectResponse
    {
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
            'lines.*.expense_category_id' => ['required', 'exists:expense_categories,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.total' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'expenseCategories' => ExpenseCategory::orderBy('name')->get(['id', 'name']),
        ];
    }
}
