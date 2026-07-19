<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Events\PurchaseSentToRfq;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRfq;
use RuntimeException;

class PurchaseToRfqService
{
    public function moveToRfq(PurchaseRequest $purchaseRequest): PurchaseRfq
    {
        if ($purchaseRequest->status !== PurchaseStatus::Approved) {
            throw new RuntimeException('Only fully approved purchase requests can be moved to RFQ.');
        }

        $purchaseRfq = $purchaseRequest->rfq()->firstOrCreate([], [
            'status' => 'pending',
        ]);

        $purchaseRequest->update(['status' => PurchaseStatus::InRfq]);

        return $purchaseRfq;
    }

    public function sendToAccounting(PurchaseRfq $purchaseRfq): void
    {
        $purchaseRfq->update([
            'status' => 'sent',
            'sent_to_accounting_at' => now(),
        ]);

        $purchaseRfq->purchaseRequest->update(['status' => PurchaseStatus::SentToAccounting]);

        event(new PurchaseSentToRfq($purchaseRfq));
    }
}
