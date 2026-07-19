<?php

namespace App\Events;

use App\Models\PurchaseRfq;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseSentToRfq
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PurchaseRfq $purchaseRfq)
    {
    }
}
