<?php

namespace App\Console\Commands;

use App\Enums\Area;
use App\Models\StaffMember;
use App\Notifications\LicencesExpiring;
use App\Support\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Daily: emails HR (ALLPRO_NOTIFY_HR, else ALLPRO_NOTIFY_EMAIL) one digest of staff PSA licences
 * that came within StaffMember::EXPIRY_WARNING_DAYS of expiry, or expired, since the last run.
 * Each licence is mentioned once per stage; a new expiry date resets it.
 */
class RemindExpiringLicences extends Command
{
    protected $signature = 'app:remind-expiring {--dry-run : List who would be included without sending}';

    protected $description = 'Email HR about staff PSA licences that are expiring or have expired';

    public function handle(): int
    {
        $due = StaffMember::licenceDue()->orderBy('psa_licence_expires_on')->get()
            ->filter(fn (StaffMember $s) => $s->psa_reminder_stage < $s->dueStage())->values();

        foreach ($due as $s) {
            $this->line("{$s->employee_no} {$s->full_name}: {$s->psaState()} ({$s->psa_licence_expires_on?->format('Y-m-d')})");
        }
        if ($this->option('dry-run') || $due->isEmpty()) {
            $this->info($due->count().' licence(s) to report.');

            return self::SUCCESS;
        }
        $to = Notify::recipients(Area::Staff);
        if ($to === []) {
            Log::warning('ALLPRO_NOTIFY_HR is not set; PSA licence reminders were not sent.');
            $this->warn('No HR inbox configured (ALLPRO_NOTIFY_HR or ALLPRO_NOTIFY_EMAIL); nothing sent.');

            return self::SUCCESS;
        }

        // Claim each licence for its stage before emailing (conditional UPDATE), so two
        // overlapping runs never report the same licence twice.
        $claimed = $due->filter(fn (StaffMember $s) => StaffMember::whereKey($s->id)->where('psa_reminder_stage', '<', $s->dueStage())
            ->update(['psa_reminder_stage' => $s->dueStage(), 'psa_reminded_at' => now()]) === 1)->values();
        if ($claimed->isEmpty()) {
            return self::SUCCESS;
        }
        // Sent straight away: a console run has no response to defer behind.
        Notification::route('mail', $to)->notify(new LicencesExpiring($claimed));
        foreach ($claimed as $s) {
            $s->log('HR emailed: PSA licence '.($s->psaState() === 'expired' ? 'has expired.' : 'expires on '.$s->psa_licence_expires_on?->format('j F Y').'.'));
        }
        $this->info($claimed->count().' licence(s) reported to HR.');

        return self::SUCCESS;
    }
}
