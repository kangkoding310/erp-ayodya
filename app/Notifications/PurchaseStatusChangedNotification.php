<?php

namespace App\Notifications;

use App\Enums\PurchaseStatus;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public PurchaseRequest $purchaseRequest, public PurchaseStatus $status)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Purchase Request {$this->purchaseRequest->code}: {$this->status->label()}")
            ->line("Your purchase request {$this->purchaseRequest->code} is now {$this->status->label()}.")
            ->action('View Request', url("/purchase/requests/{$this->purchaseRequest->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'purchase_request_id' => $this->purchaseRequest->id,
            'purchase_request_code' => $this->purchaseRequest->code,
            'status' => $this->status->value,
        ];
    }
}
