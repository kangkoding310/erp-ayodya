<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InApproval = 'in_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case InRfq = 'in_rfq';
    case SentToAccounting = 'sent_to_accounting';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::InApproval => 'In Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::InRfq => 'In RFQ',
            self::SentToAccounting => 'Sent to Accounting',
        };
    }
}
