<?php

namespace App\Observers;

use App\Models\ExpenseReportLine;

class ExpenseReportObserver
{
    public function saved(ExpenseReportLine $line): void
    {
        $this->recalculateTotal($line);
    }

    public function deleted(ExpenseReportLine $line): void
    {
        $this->recalculateTotal($line);
    }

    private function recalculateTotal(ExpenseReportLine $line): void
    {
        $expenseReport = $line->expenseReport;

        $expenseReport->update([
            'total_expense' => $expenseReport->lines()->sum('total'),
        ]);
    }
}
