<?php

namespace App\Listeners;

use App\Enums\ApprovalStatus;
use App\Events\PurchaseRequestSubmitted;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyNextApprover implements ShouldQueue
{
    public function handle(PurchaseRequestSubmitted $event): void
    {
        $nextApproval = $event->purchaseRequest->approvals()
            ->where('status', ApprovalStatus::Pending)
            ->with('approvalMatrixLevel', 'approver')
            ->get()
            ->sortBy(fn ($approval) => $approval->approvalMatrixLevel->level)
            ->first();

        if ($nextApproval) {
            $nextApproval->approver->notify(new ApprovalRequestedNotification($nextApproval));
        }
    }
}
