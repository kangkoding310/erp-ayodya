<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ExpenseStatus;
use App\Models\ApprovalMatrix;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportApproval;
use App\Notifications\ExpenseApprovalRequestedNotification;
use App\Notifications\ExpenseStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Routes expense report approvals from the highest approval-matrix level down
 * to the lowest (e.g. level 2 approves before level 1), the reverse of the
 * purchase request flow.
 */
class ExpenseApprovalRoutingService
{
    public function submit(ExpenseReport $expenseReport): void
    {
        $matrix = ApprovalMatrix::query()
            ->whereHas('purchaseType', fn ($q) => $q->where('name', 'Expense'))
            ->where('is_active', true)
            ->with('levels')
            ->first();

        if (! $matrix || $matrix->levels->isEmpty()) {
            throw new RuntimeException("No active approval matrix configured for the 'Expense' purchase type.");
        }

        DB::transaction(function () use ($expenseReport, $matrix) {
            foreach ($matrix->levels as $level) {
                ExpenseReportApproval::create([
                    'expense_report_id' => $expenseReport->id,
                    'approval_matrix_level_id' => $level->id,
                    'approver_id' => $level->approver_id,
                    'status' => ApprovalStatus::Pending,
                ]);
            }

            $expenseReport->update(['status' => ExpenseStatus::InApproval]);
        });

        $firstApproval = $this->currentApproval($expenseReport);

        if ($firstApproval) {
            $firstApproval->approver->notify(new ExpenseApprovalRequestedNotification($firstApproval));
        }
    }

    /**
     * The approval that is actually up for review right now: the pending
     * approval with the highest matrix level. Sibling approvals for lower
     * levels exist in the database (so the full pipeline can be displayed)
     * but aren't this approver's turn yet.
     */
    public function currentApproval(ExpenseReport $expenseReport): ?ExpenseReportApproval
    {
        return $expenseReport->approvals()
            ->where('status', ApprovalStatus::Pending)
            ->with('approvalMatrixLevel', 'approver')
            ->get()
            ->sortByDesc(fn (ExpenseReportApproval $pending) => $pending->approvalMatrixLevel->level)
            ->first();
    }

    public function isCurrentTurn(ExpenseReportApproval $approval): bool
    {
        return $approval->status === ApprovalStatus::Pending
            && $this->currentApproval($approval->expenseReport)?->id === $approval->id;
    }

    public function approve(ExpenseReportApproval $approval, ?string $remarks = null): void
    {
        $approval->update([
            'status' => ApprovalStatus::Approved,
            'remarks' => $remarks,
            'approved_at' => now(),
        ]);

        $expenseReport = $approval->expenseReport;

        $nextApproval = $this->currentApproval($expenseReport);

        if ($nextApproval) {
            $nextApproval->approver->notify(new ExpenseApprovalRequestedNotification($nextApproval));

            return;
        }

        $expenseReport->update(['status' => ExpenseStatus::Approved]);
        $expenseReport->employee->user->notify(
            new ExpenseStatusChangedNotification($expenseReport, ExpenseStatus::Approved)
        );
    }

    public function reject(ExpenseReportApproval $approval, string $remarks): void
    {
        $approval->update([
            'status' => ApprovalStatus::Rejected,
            'remarks' => $remarks,
            'approved_at' => now(),
        ]);

        $expenseReport = $approval->expenseReport;
        $expenseReport->update(['status' => ExpenseStatus::Rejected]);

        $expenseReport->employee->user->notify(
            new ExpenseStatusChangedNotification($expenseReport, ExpenseStatus::Rejected)
        );
    }
}
