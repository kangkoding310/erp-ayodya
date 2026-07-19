<?php

namespace App\Observers;

use App\Models\PurchaseRequestLine;

class PurchaseRequestObserver
{
    public function saved(PurchaseRequestLine $line): void
    {
        $this->recalculateTotal($line);
    }

    public function deleted(PurchaseRequestLine $line): void
    {
        $this->recalculateTotal($line);
    }

    private function recalculateTotal(PurchaseRequestLine $line): void
    {
        $purchaseRequest = $line->purchaseRequest;

        $purchaseRequest->update([
            'total_amount' => $purchaseRequest->lines()->sum('subtotal'),
        ]);
    }
}
