<?php

namespace App\Enums;

enum BillSourceType: string
{
    case Purchase = 'purchase';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Expense => 'Expense',
        };
    }
}
