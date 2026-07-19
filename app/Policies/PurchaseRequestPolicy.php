<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->requested_by
            || $user->hasRole('approver')
            || $user->hasRole('finance');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->requested_by
            && $purchaseRequest->status->value === 'draft';
    }

    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->requested_by
            && $purchaseRequest->status->value === 'draft';
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $purchaseRequest->approvals()
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }
}
