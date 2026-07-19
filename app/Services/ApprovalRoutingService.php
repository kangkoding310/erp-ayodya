<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Events\PurchaseApproved;
use App\Events\PurchaseRejected;
use App\Events\PurchaseRequestSubmitted;
use App\Models\ApprovalMatrix;
use App\Models\PurchaseApproval;
use App\Models\PurchaseRequest;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\PurchaseStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApprovalRoutingService
{
    public function submit(PurchaseRequest $purchaseRequest): void
    {
        $matrix = ApprovalMatrix::query()
            ->where('purchase_type_id', $purchaseRequest->purchase_type_id)
            ->where('model_type', PurchaseRequest::class)
            ->where('is_active', true)
            ->with('levels')
            ->first();

        if (! $matrix || $matrix->levels->isEmpty()) {
            throw new RuntimeException('No active approval matrix configured for this purchase type.');
        }

        DB::transaction(function () use ($purchaseRequest, $matrix) {
            foreach ($matrix->levels as $level) {
                PurchaseApproval::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'approval_matrix_level_id' => $level->id,
                    'approver_id' => $level->approver_id,
                    'status' => ApprovalStatus::Pending,
                ]);
            }

            $purchaseRequest->update(['status' => PurchaseStatus::InApproval]);
        });

        event(new PurchaseRequestSubmitted($purchaseRequest));
    }

    public function approve(PurchaseApproval $approval, ?string $remarks = null): void
    {
        $approval->update([
            'status' => ApprovalStatus::Approved,
            'remarks' => $remarks,
            'approved_at' => now(),
        ]);

        $purchaseRequest = $approval->purchaseRequest;

        $nextApproval = $purchaseRequest->approvals()
            ->where('status', ApprovalStatus::Pending)
            ->with('approvalMatrixLevel', 'approver')
            ->get()
            ->sortBy(fn (PurchaseApproval $pending) => $pending->approvalMatrixLevel->level)
            ->first();

        if ($nextApproval) {
            $nextApproval->approver->notify(new ApprovalRequestedNotification($nextApproval));

            return;
        }

        $purchaseRequest->update(['status' => PurchaseStatus::Approved]);
        $purchaseRequest->requestedBy->notify(
            new PurchaseStatusChangedNotification($purchaseRequest, PurchaseStatus::Approved)
        );

        event(new PurchaseApproved($purchaseRequest));
    }

    public function reject(PurchaseApproval $approval, ?string $remarks = null): void
    {
        $approval->update([
            'status' => ApprovalStatus::Rejected,
            'remarks' => $remarks,
            'approved_at' => now(),
        ]);

        $purchaseRequest = $approval->purchaseRequest;
        $purchaseRequest->update(['status' => PurchaseStatus::Rejected]);

        $purchaseRequest->requestedBy->notify(
            new PurchaseStatusChangedNotification($purchaseRequest, PurchaseStatus::Rejected)
        );

        event(new PurchaseRejected($purchaseRequest, $approval));
    }
}
