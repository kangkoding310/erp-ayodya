<?php

namespace App\Notifications;

use App\Models\ExpenseReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public ExpenseReport $expenseReport, public int $pendingLineCount)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lineWord = $this->pendingLineCount === 1 ? 'line' : 'lines';

        return (new MailMessage)
            ->subject("Approval Requested: {$this->expenseReport->code}")
            ->line("Expense report {$this->expenseReport->code} has {$this->pendingLineCount} {$lineWord} needing your approval.")
            ->action('Review Report', url("/expense/approvals/{$this->expenseReport->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'expense_report_id' => $this->expenseReport->id,
            'expense_report_code' => $this->expenseReport->code,
            'pending_line_count' => $this->pendingLineCount,
        ];
    }
}
