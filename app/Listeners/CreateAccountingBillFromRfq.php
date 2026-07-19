<?php

namespace App\Listeners;

use App\Enums\BillSourceType;
use App\Events\PurchaseSentToRfq;
use App\Models\AccountingBill;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateAccountingBillFromRfq implements ShouldQueue
{
    public function handle(PurchaseSentToRfq $event): void
    {
        $purchaseRfq = $event->purchaseRfq;
        $purchaseRequest = $purchaseRfq->purchaseRequest;

        AccountingBill::create([
            'source_type' => BillSourceType::Purchase->value,
            'source_id' => $purchaseRfq->id,
            'amount' => $purchaseRequest->total_amount,
            'status' => 'unpaid',
        ]);
    }
}
