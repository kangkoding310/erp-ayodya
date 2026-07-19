<?php

namespace App\Notifications;

use App\Models\ExpenseReportApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public ExpenseReportApproval $approval)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expenseReport = $this->approval->expenseReport;

        return (new MailMessage)
            ->subject("Approval Requested: {$expenseReport->code}")
            ->line("Expense report {$expenseReport->code} needs your approval.")
            ->action('Review Report', url("/expense/approvals/{$this->approval->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'expense_report_approval_id' => $this->approval->id,
            'expense_report_id' => $this->approval->expense_report_id,
            'expense_report_code' => $this->approval->expenseReport->code,
        ];
    }
}
