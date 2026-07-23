<?php

namespace App\Support;

use App\Enums\ApprovalStatus;
use App\Enums\BillSourceType;
use App\Enums\ExpenseStatus;
use App\Models\AccountingBill;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportLineApproval;
use Illuminate\Support\Collection;

/**
 * Assembles a single chronological history for an expense report out of the
 * pieces that are actually stored: the report itself, each line's approval
 * decisions, and the accounting bill created on send. There is no dedicated
 * event log table, so timestamps are inferred from the records that exist.
 */
class ExpenseApprovalHistoryBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(ExpenseReport $expenseReport): array
    {
        $events = [];

        $events[] = [
            'type' => 'created',
            'at' => $expenseReport->created_at->toIso8601String(),
            'actor' => $this->employeeActor($expenseReport),
        ];

        $lineApprovals = $expenseReport->lines
            ->flatMap(fn ($line) => $line->lineApprovals->map(function (ExpenseReportLineApproval $approval) use ($line) {
                $approval->setRelation('expenseReportLine', $line);

                return $approval;
            }));

        $events = array_merge($events, $this->submissionEvents($lineApprovals));
        $events = array_merge($events, $this->decisionEvents($lineApprovals));

        if ($expenseReport->status === ExpenseStatus::Approved || $expenseReport->status === ExpenseStatus::SentToAccounting) {
            $lastApproval = $lineApprovals->where('status', ApprovalStatus::Approved)->sortByDesc('approved_at')->first();

            if ($lastApproval) {
                $events[] = [
                    'type' => 'report_approved',
                    'at' => $lastApproval->approved_at->toIso8601String(),
                ];
            }
        }

        if ($expenseReport->status === ExpenseStatus::SentToAccounting) {
            $bill = AccountingBill::query()
                ->where('source_type', BillSourceType::Expense)
                ->where('source_id', $expenseReport->id)
                ->first();

            if ($bill) {
                $events[] = [
                    'type' => 'sent_to_accounting',
                    'at' => $bill->created_at->toIso8601String(),
                ];
            }
        }

        if ($expenseReport->status === ExpenseStatus::Cancelled) {
            $events[] = [
                'type' => 'cancelled',
                'at' => $expenseReport->updated_at->toIso8601String(),
            ];
        }

        usort($events, fn ($a, $b) => $a['at'] <=> $b['at']);

        $stillInProgress = in_array($expenseReport->status, [ExpenseStatus::InApproval, ExpenseStatus::NeedsRevision], true);

        if ($stillInProgress) {
            $events = array_merge($events, $this->inProgressEvents($lineApprovals));
        }

        return $events;
    }

    /**
     * The steps that haven't happened yet: for each line still awaiting a
     * decision, the highest pending level is "current" (it's genuinely someone's
     * turn right now); any lower pending levels for that line are "future" steps
     * that only become actionable once the current one clears. Both are grouped
     * by (approver, level) so a single batch of lines shows as one node, same as
     * the historical decision events.
     *
     * @param  Collection<int, ExpenseReportLineApproval>  $lineApprovals
     * @return array<int, array<string, mixed>>
     */
    private function inProgressEvents(Collection $lineApprovals): array
    {
        $pendingByLine = $lineApprovals
            ->where('status', ApprovalStatus::Pending)
            ->groupBy('expense_report_line_id');

        $current = collect();
        $future = collect();

        foreach ($pendingByLine as $pending) {
            $sorted = $pending->sortByDesc(fn (ExpenseReportLineApproval $a) => $a->approvalMatrixLevel->level)->values();
            $current->push($sorted->first());
            $future = $future->merge($sorted->slice(1));
        }

        $groupKey = fn (ExpenseReportLineApproval $a) => implode('|', [$a->approver_id, $a->approvalMatrixLevel->level]);

        $toEvents = function (Collection $items, string $type) use ($groupKey) {
            return $items->groupBy($groupKey)->values()->map(function (Collection $batch) use ($type) {
                /** @var ExpenseReportLineApproval $first */
                $first = $batch->first();

                return [
                    'type' => $type,
                    'level' => $first->approvalMatrixLevel->level,
                    'approver' => ['id' => $first->approver->id, 'name' => $first->approver->name],
                    'lines' => $batch->map(fn (ExpenseReportLineApproval $a) => [
                        'id' => $a->expenseReportLine->id,
                        'description' => $a->expenseReportLine->description,
                        'remarks' => $a->remarks,
                    ])->all(),
                ];
            })->sortByDesc('level')->values()->all();
        };

        return array_merge($toEvents($current, 'pending_approval'), $toEvents($future, 'future_approval'));
    }

    /**
     * @param  Collection<int, ExpenseReportLineApproval>  $lineApprovals
     * @return array<int, array<string, mixed>>
     */
    private function submissionEvents(Collection $lineApprovals): array
    {
        $batches = $lineApprovals->groupBy(fn (ExpenseReportLineApproval $a) => $a->created_at->toIso8601String())->sortKeys();

        return $batches->values()->map(fn ($batch, $index) => [
            'type' => $index === 0 ? 'submitted' : 'resubmitted',
            'at' => $batch->first()->created_at->toIso8601String(),
        ])->all();
    }

    /**
     * @param  Collection<int, ExpenseReportLineApproval>  $lineApprovals
     * @return array<int, array<string, mixed>>
     */
    private function decisionEvents(Collection $lineApprovals): array
    {
        $decided = $lineApprovals->whereIn('status', [ApprovalStatus::Approved, ApprovalStatus::Rejected]);

        $grouped = $decided->groupBy(fn (ExpenseReportLineApproval $a) => implode('|', [
            $a->approver_id,
            $a->approved_at?->toIso8601String(),
            $a->status->value,
        ]));

        return $grouped->values()->map(function (Collection $batch) {
            /** @var ExpenseReportLineApproval $first */
            $first = $batch->first();

            $lines = $batch->map(fn (ExpenseReportLineApproval $a) => [
                'id' => $a->expenseReportLine->id,
                'description' => $a->expenseReportLine->description,
                'remarks' => $a->remarks,
            ])->all();

            return [
                'type' => $first->status === ApprovalStatus::Approved ? 'lines_approved' : 'lines_rejected',
                'at' => $first->approved_at->toIso8601String(),
                'level' => $first->approvalMatrixLevel->level,
                'approver' => ['id' => $first->approver->id, 'name' => $first->approver->name],
                'lines' => $lines,
            ];
        })->all();
    }

    private function employeeActor(ExpenseReport $expenseReport): ?array
    {
        $user = $expenseReport->employee?->user;

        return $user ? ['id' => $user->id, 'name' => $user->name] : null;
    }
}
