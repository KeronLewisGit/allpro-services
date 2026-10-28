<?php

namespace App\Notifications;

use App\Models\StaffMember;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** The morning digest to HR: staff PSA licences that have expired or expire soon. */
class LicencesExpiring extends Notification
{
    /** @param Collection<int, StaffMember> $staff */
    public function __construct(public Collection $staff) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expired = $this->staff->filter(fn (StaffMember $s) => $s->psaState() === 'expired');
        $mail = (new MailMessage)
            ->subject('PSA licences: '.$this->staff->count().' to renew'.($expired->isNotEmpty() ? ', '.$expired->count().' expired' : ''))
            ->greeting('PSA licence reminders')
            ->line('These staff need a renewed PSA licence. Anyone whose licence has expired must not work in a licensed security role until the renewal is issued.');
        foreach ($this->staff as $s) {
            $when = $s->psaState() === 'expired' ? 'expired on '.$s->psa_licence_expires_on?->format('j F Y') : 'expires '.$s->psa_licence_expires_on?->format('j F Y').' ('.$s->daysLeft().' days)';
            $mail->line("- **{$s->full_name}** ({$s->employee_no}, {$s->region->label()}), licence {$s->psa_licence_number}: {$when}");
        }

        return $mail->action('Open the licence register', route('admin.staff.index', ['due' => 1]))
            ->line('You get one email when a licence comes within '.StaffMember::EXPIRY_WARNING_DAYS.' days of expiry and one more if it expires. Updating the expiry date on the register starts the reminders again.');
    }
}
