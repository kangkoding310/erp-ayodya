<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ExpenseStatus;
use App\Models\ApprovalMatrix;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportLine;
use App\Models\ExpenseReportLineApproval;
use App\Notifications\ExpenseApprovalRequestedNotification;
use App\Notifications\ExpenseStatusChangedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Routes expense report line approvals from the highest approval-matrix level
 * down to the lowest (e.g. level 2 approves before level 1), the reverse of
 * the purchase request flow. Each line moves through the matrix independently.
 */
class ExpenseApprovalRoutingService
{
    public function submit(ExpenseReport $expenseReport): void
    {
        $matrix = $this->activeMatrix();

        DB::transaction(function () use ($expenseReport, $matrix) {
            foreach ($expenseReport->lines as $line) {
                foreach ($matrix->levels as $level) {
                    ExpenseReportLineApproval::create([
                        'expense_report_line_id' => $line->id,
                        'approval_matrix_level_id' => $level->id,
                        'cycle' => 1,
                        'approver_id' => $level->approver_id,
                        'status' => ApprovalStatus::Pending,
                    ]);
                }

                $line->update(['status' => ApprovalStatus::Pending]);
            }

            $expenseReport->update(['status' => ExpenseStatus::InApproval]);
        });

        $this->notifyCurrentApprovers($expenseReport->fresh());
    }

    /**
     * The line-approval that is actually up for review right now. Levels move
     * in lockstep across the whole report: a line only reaches its next
     * (lower) level once every other line has cleared the level currently
     * active for the report, so a handful of early approvals can't race
     * ahead to level 1 while siblings are still stuck at level 2.
     */
    public function currentLineApproval(ExpenseReportLine $line): ?ExpenseReportLineApproval
    {
        $activeLevel = $this->activeLevelForReport($line->expenseReport);

        if ($activeLevel === null) {
            return null;
        }

        return $line->lineApprovals()
            ->where('status', ApprovalStatus::Pending)
            ->whereHas('approvalMatrixLevel', fn ($q) => $q->where('level', $activeLevel))
            ->with('approvalMatrixLevel', 'approver')
            ->first();
    }

    /**
     * Name of whoever needs to act next on this report, for display on report
     * listings (e.g. "In Approval by Dede"). Null once nobody's approval is
     * outstanding (report fully approved, rejected, still a draft, etc.).
     */
    public function currentApproverName(ExpenseReport $expenseReport): ?string
    {
        foreach ($expenseReport->lines as $line) {
            $current = $this->currentLineApproval($line);

            if ($current) {
                return $current->approver->name;
            }
        }

        return null;
    }

    /**
     * The highest matrix level that still has a pending approval anywhere on
     * the report. That's the level currently "in play" for every line at
     * once; lines that already cleared it simply wait for their siblings.
     */
    private function activeLevelForReport(ExpenseReport $expenseReport): ?int
    {
        return ExpenseReportLineApproval::query()
            ->whereHas('expenseReportLine', fn ($q) => $q->where('expense_report_id', $expenseReport->id))
            ->where('status', ApprovalStatus::Pending)
            ->with('approvalMatrixLevel')
            ->get()
            ->max(fn (ExpenseReportLineApproval $pending) => $pending->approvalMatrixLevel->level);
    }

    public function isCurrentTurn(ExpenseReportLineApproval $lineApproval): bool
    {
        return $lineApproval->status === ApprovalStatus::Pending
            && $this->currentLineApproval($lineApproval->expenseReportLine)?->id === $lineApproval->id;
    }

    /**
     * Lines where it is currently this approver's turn to decide, across every
     * report. Filtering "is it my turn" can't be expressed purely in SQL since
     * it depends on sibling rows, so pending rows are fetched and filtered in PHP.
     *
     * @return Collection<int, ExpenseReportLine>
     */
    public function pendingLinesForApprover(int $userId): Collection
    {
        $pendingApprovals = ExpenseReportLineApproval::query()
            ->where('approver_id', $userId)
            ->where('status', ApprovalStatus::Pending)
            ->with('expenseReportLine.expenseReport', 'approvalMatrixLevel')
            ->get();

        return $pendingApprovals
            ->filter(fn (ExpenseReportLineApproval $approval) => $this->isCurrentTurn($approval))
            ->map(fn (ExpenseReportLineApproval $approval) => $approval->expenseReportLine)
            ->unique('id')
            ->values();
    }

    /**
     * Every report this user is (or was) an approver on any line of, whether
     * it's currently their turn or not — from the moment it enters the
     * approval pipeline through to being fully approved. Drafts and cancelled
     * reports are excluded since they're no longer relevant to an approver.
     */
    public function reportsForApprover(int $userId): Builder
    {
        return ExpenseReport::query()
            ->whereHas('lines.lineApprovals', fn ($q) => $q->where('approver_id', $userId))
            ->whereNotIn('status', [ExpenseStatus::Draft, ExpenseStatus::Cancelled]);
    }

    /**
     * @param  array<int>  $lineIds
     * @param  array<int, string|null>  $remarksByLineId  keyed by line id
     */
    public function bulkApprove(ExpenseReport $expenseReport, array $lineIds, array $remarksByLineId, int $approverId): void
    {
        $lines = $this->guardedTurnLines($expenseReport, $lineIds, $approverId);
        $decidedAt = now();

        DB::transaction(function () use ($lines, $remarksByLineId, $decidedAt) {
            foreach ($lines as $line) {
                $current = $this->currentLineApproval($line);

                $current->update([
                    'status' => ApprovalStatus::Approved,
                    'remarks' => $remarksByLineId[$line->id] ?? null,
                    'approved_at' => $decidedAt,
                ]);

                if (! $line->lineApprovals()->where('status', ApprovalStatus::Pending)->exists()) {
                    $line->update(['status' => ApprovalStatus::Approved]);
                }
            }
        });

        $expenseReport = $expenseReport->fresh();
        $this->recomputeReportStatus($expenseReport);
        $this->notifyCurrentApprovers($expenseReport->fresh());
    }

    /**
     * @param  array<int>  $lineIds
     * @param  array<int, string|null>  $remarksByLineId  keyed by line id
     */
    public function bulkReject(ExpenseReport $expenseReport, array $lineIds, array $remarksByLineId, ?string $bulkFallbackRemark, int $approverId): void
    {
        $lines = $this->guardedTurnLines($expenseReport, $lineIds, $approverId);
        $decidedAt = now();

        DB::transaction(function () use ($lines, $remarksByLineId, $bulkFallbackRemark, $decidedAt) {
            foreach ($lines as $line) {
                $current = $this->currentLineApproval($line);
                $remark = $remarksByLineId[$line->id] ?: $bulkFallbackRemark;

                $current->update([
                    'status' => ApprovalStatus::Rejected,
                    'remarks' => $remark,
                    'approved_at' => $decidedAt,
                ]);

                $line->lineApprovals()
                    ->where('status', ApprovalStatus::Pending)
                    ->update(['status' => ApprovalStatus::Skipped, 'approved_at' => $decidedAt]);

                $line->update(['status' => ApprovalStatus::Rejected]);
            }
        });

        $expenseReport = $expenseReport->fresh();
        $this->recomputeReportStatus($expenseReport);

        $expenseReport->employee->user->notify(
            new ExpenseStatusChangedNotification($expenseReport, ExpenseStatus::NeedsRevision)
        );
    }

    /**
     * Resets the given rejected lines back into the approval pipeline from the
     * top level, leaving already-approved lines untouched. Supports partial
     * resubmission: the report stays "needs revision" until every rejected
     * line has been cleared.
     *
     * @param  array<int>  $lineIds
     */
    public function resubmitAfterRevision(ExpenseReport $expenseReport, array $lineIds): void
    {
        $matrix = $this->activeMatrix();

        $lines = $expenseReport->lines()
            ->whereIn('id', $lineIds)
            ->where('status', ApprovalStatus::Rejected)
            ->get();

        abort_if($lines->count() !== count($lineIds), 422, 'Only rejected lines can be resubmitted.');

        DB::transaction(function () use ($lines, $matrix) {
            foreach ($lines as $line) {
                // Start a fresh cycle instead of deleting prior rows, so the rejection
                // (and its remark) that triggered this resubmission stays visible in the
                // approval history rather than being wiped from under it.
                $nextCycle = ($line->lineApprovals()->max('cycle') ?? 0) + 1;

                foreach ($matrix->levels as $level) {
                    ExpenseReportLineApproval::create([
                        'expense_report_line_id' => $line->id,
                        'approval_matrix_level_id' => $level->id,
                        'cycle' => $nextCycle,
                        'approver_id' => $level->approver_id,
                        'status' => ApprovalStatus::Pending,
                    ]);
                }

                $line->update(['status' => ApprovalStatus::Pending]);
            }
        });

        $expenseReport = $expenseReport->fresh();
        $hasRejectedLines = $expenseReport->lines()->where('status', ApprovalStatus::Rejected)->exists();

        $expenseReport->update([
            'status' => $hasRejectedLines ? ExpenseStatus::NeedsRevision : ExpenseStatus::InApproval,
        ]);

        $this->notifyCurrentApprovers($expenseReport->fresh());
    }

    /**
     * Recomputes the report's overall status from its lines' current outcomes.
     * Any rejected line (whether some or all lines) sends the report back for
     * revision rather than a terminal rejection, since the employee can always
     * fix and resubmit at the line level.
     */
    private function recomputeReportStatus(ExpenseReport $expenseReport): void
    {
        $lines = $expenseReport->lines()->get();

        if ($lines->contains(fn (ExpenseReportLine $line) => $line->status === ApprovalStatus::Rejected)) {
            $expenseReport->update(['status' => ExpenseStatus::NeedsRevision]);

            return;
        }

        if ($lines->every(fn (ExpenseReportLine $line) => $line->status === ApprovalStatus::Approved)) {
            $expenseReport->update(['status' => ExpenseStatus::Approved]);

            $expenseReport->employee->user->notify(
                new ExpenseStatusChangedNotification($expenseReport, ExpenseStatus::Approved)
            );
        }
    }

    /**
     * Notifies each distinct approver whose turn it currently is, once per
     * approver (not once per line), with how many lines are awaiting them.
     */
    private function notifyCurrentApprovers(ExpenseReport $expenseReport): void
    {
        $currentApprovals = $expenseReport->lines
            ->map(fn (ExpenseReportLine $line) => $this->currentLineApproval($line))
            ->filter();

        foreach ($currentApprovals->groupBy('approver_id') as $approvals) {
            $approvals->first()->approver->notify(
                new ExpenseApprovalRequestedNotification($expenseReport, $approvals->count())
            );
        }
    }

    /**
     * Loads the requested lines and asserts every one of them is currently
     * this approver's turn to decide.
     *
     * @param  array<int>  $lineIds
     * @return Collection<int, ExpenseReportLine>
     */
    private function guardedTurnLines(ExpenseReport $expenseReport, array $lineIds, int $approverId): Collection
    {
        $lines = $expenseReport->lines()->whereIn('id', $lineIds)->get();

        abort_if($lines->count() !== count($lineIds), 422, 'One or more selected lines were not found on this report.');

        foreach ($lines as $line) {
            $current = $this->currentLineApproval($line);

            abort_unless($current && $current->approver_id === $approverId, 403);
            abort_unless($this->isCurrentTurn($current), 409);
        }

        return $lines;
    }

    private function activeMatrix(): ApprovalMatrix
    {
        $matrix = ApprovalMatrix::query()
            ->whereHas('purchaseType', fn ($q) => $q->where('name', 'Expense'))
            ->where('is_active', true)
            ->with('levels')
            ->first();

        if (! $matrix || $matrix->levels->isEmpty()) {
            throw new RuntimeException("No active approval matrix configured for the 'Expense' purchase type.");
        }

        return $matrix;
    }
}
