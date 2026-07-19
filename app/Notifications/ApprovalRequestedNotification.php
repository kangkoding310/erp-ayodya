<?php

namespace App\Notifications;

use App\Models\PurchaseApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public PurchaseApproval $approval)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $purchaseRequest = $this->approval->purchaseRequest;

        return (new MailMessage)
            ->subject("Approval Requested: {$purchaseRequest->code}")
            ->line("Purchase request {$purchaseRequest->code} needs your approval.")
            ->action('Review Request', url("/purchase/approvals/{$this->approval->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'purchase_approval_id' => $this->approval->id,
            'purchase_request_id' => $this->approval->purchase_request_id,
            'purchase_request_code' => $this->approval->purchaseRequest->code,
        ];
    }
}
