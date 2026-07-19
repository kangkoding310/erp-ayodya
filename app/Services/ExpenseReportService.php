<?php

namespace App\Services;

use App\Enums\BillSourceType;
use App\Enums\ExpenseStatus;
use App\Models\AccountingBill;
use App\Models\ExpenseReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExpenseReportService
{
    public function generateCode(?Carbon $date = null): string
    {
        $date ??= now();
        $month = $date->format('m');
        $year = $date->format('Y');
        $key = "EXP-{$year}-{$month}";

        $sequence = DB::selectOne(
            <<<'SQL'
                insert into number_sequences (key, last_number, created_at, updated_at)
                values (?, 1, now(), now())
                on conflict (key) do update
                    set last_number = number_sequences.last_number + 1,
                        updated_at = now()
                returning last_number
                SQL,
            [$key]
        )->last_number;

        return sprintf('EXP/%s/%s/%03d', $month, $year, $sequence);
    }

    public function sendToAccounting(ExpenseReport $expenseReport): AccountingBill
    {
        if ($expenseReport->status !== ExpenseStatus::Approved) {
            throw new RuntimeException('Only approved expense reports can be sent to accounting.');
        }

        $bill = AccountingBill::create([
            'source_type' => BillSourceType::Expense->value,
            'source_id' => $expenseReport->id,
            'amount' => $expenseReport->total_expense,
            'status' => 'unpaid',
        ]);

        $expenseReport->update(['status' => ExpenseStatus::SentToAccounting]);

        return $bill;
    }
}
