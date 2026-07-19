<?php

namespace App\Notifications;

use App\Enums\ExpenseStatus;
use App\Models\ExpenseReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public ExpenseReport $expenseReport, public ExpenseStatus $status)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Expense Report {$this->expenseReport->code}: {$this->status->label()}")
            ->line("Your expense report {$this->expenseReport->code} is now {$this->status->label()}.")
            ->action('View Report', url("/expense/reports/{$this->expenseReport->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'expense_report_id' => $this->expenseReport->id,
            'expense_report_code' => $this->expenseReport->code,
            'status' => $this->status->value,
        ];
    }
}
