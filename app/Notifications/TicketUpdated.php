<?php

namespace App\Notifications;

use App\Models\ServiceTicket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the person who logged a ticket: an update from operations. */
class TicketUpdated extends Notification
{
    public function __construct(public ServiceTicket $ticket, public string $message) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->ticket;

        return (new MailMessage)
            ->subject("Update on {$t->reference}: {$t->status->label()}")
            ->greeting('Hello '.$t->reporter_name.',')
            ->line("Your {$t->type->label()} at {$t->account->site_name} is now **{$t->status->label()}**.")
            ->line($this->message)
            ->line('Questions: '.format_phone((string) allpro('phone')).' or '.allpro('email').', quoting '.$t->reference.'.')
            ->salutation('Allpro Services operations');
    }
}
