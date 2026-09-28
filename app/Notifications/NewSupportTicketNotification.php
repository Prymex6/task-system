<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSupportTicketNotification extends Notification
{
    use Queueable;

    public function __construct(public $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_support_ticket',
            'title' => 'Nowy ticket wsparcia',
            'message' => "Nowy ticket: \"{$this->ticket->subject}\"",
            'ticket_id' => $this->ticket->id,
            'url' => "/manager/support/{$this->ticket->id}",
        ];
    }
}
