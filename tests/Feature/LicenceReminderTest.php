<?php

namespace Tests\Feature;

use App\Models\StaffMember;
use App\Notifications\LicencesExpiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** app:remind-expiring: HR's daily digest of PSA licences, one email per licence per stage. */
class LicenceReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['allpro.notify.staff' => 'hr@example.com', 'allpro.notify.default' => 'office@example.com']);
    }

    public function test_hr_gets_one_digest_per_stage(): void
    {
        $soon = StaffMember::factory()->expiringIn(20)->create();
        $gone = StaffMember::factory()->expiringIn(-1)->create();
        StaffMember::factory()->expiringIn(200)->create();
        StaffMember::factory()->expiringIn(5)->create(['active' => false]);
        StaffMember::factory()->create(['role_type' => 'cleaning_operative', 'psa_licence_number' => null, 'psa_licence_expires_on' => null]);

        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemandTimes(LicencesExpiring::class, 1);
        Notification::assertSentOnDemand(LicencesExpiring::class, fn (LicencesExpiring $n, $c, $to) => $to->routes['mail'] === ['hr@example.com']
            && $n->staff->pluck('id')->sort()->values()->all() === collect([$soon->id, $gone->id])->sort()->values()->all());
        $this->assertSame(StaffMember::STAGE_EXPIRING, $soon->refresh()->psa_reminder_stage);
        $this->assertSame(StaffMember::STAGE_EXPIRED, $gone->refresh()->psa_reminder_stage);
        $this->assertSame(1, $soon->notes()->count());

        // Nothing new tomorrow...
        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemandTimes(LicencesExpiring::class, 1);

        // ...until a licence lapses: one more email for that stage.
        $this->travel(21)->days();
        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemandTimes(LicencesExpiring::class, 2);
        $this->assertSame(StaffMember::STAGE_EXPIRED, $soon->refresh()->psa_reminder_stage);
    }

    public function test_dry_run_and_no_inbox_send_nothing(): void
    {
        $s = StaffMember::factory()->expiringIn(3)->create();
        $this->artisan('app:remind-expiring --dry-run')->expectsOutputToContain($s->employee_no)->assertSuccessful();
        config(['allpro.notify.staff' => '', 'allpro.notify.default' => '']);
        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertNothingSent();
        $this->assertSame(StaffMember::STAGE_NONE, $s->refresh()->psa_reminder_stage);
    }

    public function test_hr_falls_back_to_the_main_inbox(): void
    {
        config(['allpro.notify.staff' => '']);
        StaffMember::factory()->expiringIn(3)->create();
        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemand(LicencesExpiring::class, fn ($n, $c, $to) => $to->routes['mail'] === ['office@example.com']);
    }

    public function test_it_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('app:remind-expiring')->assertSuccessful();
    }
}
