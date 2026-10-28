<?php

namespace App\Notifications;

use App\Models\ClientAccount;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sends a client contact the signed link to their site's service desk. */
class PortalLinkShared extends Notification
{
    public function __construct(public ClientAccount $account, public string $url, public string $senderName) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Allpro service desk link: '.$this->account->site_name)
            ->greeting('Hello'.($this->account->contact_name ? ' '.$this->account->contact_name : '').',')
            ->line("{$this->senderName} has set up a private service desk for **{$this->account->displayName()}**.")
            ->line('Use it to log a request or a complaint at any time, with photos if that helps. Each one gets a reference number and a response time, and you can follow progress on the same page.')
            ->action('Open the service desk', $this->url)
            ->line('The link works until '.$this->account->link_expires_at?->format('j F Y').' and needs no password. Bookmark it, and share it only with colleagues at this site.');
    }
}
