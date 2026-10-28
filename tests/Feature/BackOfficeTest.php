<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\QuoteStatus;
use App\Enums\Region;
use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\VettingStatus;
use App\Models\ClientAccount;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\QuoteRequest;
use App\Models\ServiceTicket;
use App\Models\StaffMember;
use App\Notifications\PortalLinkShared;
use App\Notifications\TicketUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BackOfficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboards_lead_with_the_number_each_role_acts_on(): void
    {
        ServiceTicket::factory()->priority(TicketPriority::Urgent)->loggedAgo(70)->count(2)->create();
        ServiceTicket::factory()->priority(TicketPriority::Low)->create();
        $this->actingAs($this->staff(Role::Operations))->get('/admin')->assertSee('tickets have breached or will breach the SLA within the hour')->assertSee('>02<', false);
        $this->actingAs($this->staff(Role::Admin))->get('/admin')->assertSee('breach the SLA')->assertSee('>02<', false);

        QuoteRequest::factory()->surveyIn(3)->count(3)->create();
        QuoteRequest::factory()->surveyIn(20)->create();
        QuoteRequest::factory()->surveyIn(2)->create(['status' => QuoteStatus::SurveyBooked]);
        $this->actingAs($this->staff(Role::Sales))->get('/admin')->assertSee('site surveys requested for the next 7 days are not booked')->assertSee('>03<', false);

        StaffMember::factory()->expiringIn(-2)->create();
        StaffMember::factory()->expiringIn(30)->create();
        StaffMember::factory()->expiringIn(200)->create();
        StaffMember::factory()->expiringIn(10)->create(['active' => false]);
        $this->actingAs($this->staff(Role::Hr))->get('/admin')->assertSee('PSA licences have expired or expire within 60 days')->assertSee('>02<', false)->assertSee('1 already expired');
    }

    public function test_hr_records_checks_and_cannot_hire_until_they_are_done(): void
    {
        $hr = $this->staff(Role::Hr);
        $a = JobApplication::factory()->security()->create();
        $this->actingAs($hr)->patch(route('admin.applications.update', $a), ['status' => 'hired'])->assertSessionHasErrors('status');
        $this->assertSame(ApplicationStatus::New, $a->refresh()->status);

        $this->patch(route('admin.applications.checks', $a), ['vetting_status' => 'cleared', 'rtw_checked' => '1', 'psa_checked' => '1'])->assertRedirect();
        $a->refresh();
        $this->assertSame(VettingStatus::Cleared, $a->vetting_status);
        $this->assertSame($hr->id, $a->rtw_checked_by);
        $this->assertSame($hr->id, $a->psa_checked_by);
        $this->assertSame([], $a->outstandingChecks());

        $this->patch(route('admin.applications.update', $a), ['status' => 'hired'])->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Hired, $a->refresh()->status);

        $this->post(route('admin.applications.convert', $a), ['employee_no' => 'S0101'])->assertRedirect();
        $staff = StaffMember::sole();
        $this->assertSame($a->psa_licence_number, $staff->psa_licence_number);
        $this->assertSame(VettingStatus::Cleared, $staff->vetting_status);
        $this->assertSame($a->id, $staff->job_application_id);
        // A second click goes to the same record.
        $this->post(route('admin.applications.convert', $a), ['employee_no' => 'S0102'])->assertRedirect(route('admin.staff.show', $staff));
        $this->assertSame(1, StaffMember::count());
    }

    public function test_an_expired_licence_blocks_hiring_a_security_officer(): void
    {
        $a = JobApplication::factory()->security(-5)->create(['vetting_status' => VettingStatus::Cleared, 'rtw_checked_at' => now(), 'psa_checked_at' => now()]);
        $this->assertContains('PSA licence expired or missing', $a->outstandingChecks());
        $this->actingAs($this->staff(Role::Hr))->patch(route('admin.applications.update', $a), ['status' => 'hired'])->assertSessionHasErrors('status');
    }

    public function test_a_status_change_sent_twice_at_once_is_applied_once(): void
    {
        $a = JobApplication::factory()->create();
        $first = JobApplication::find($a->id);
        $second = JobApplication::find($a->id);
        $this->assertTrue($first->claimStatus(ApplicationStatus::Screening));
        $this->assertFalse($second->claimStatus(ApplicationStatus::Interview));
        $this->assertSame(ApplicationStatus::Screening, $a->refresh()->status);
    }

    public function test_sales_assigns_books_and_quotes(): void
    {
        $sales = $this->staff(Role::Sales);
        $q = QuoteRequest::factory()->create();
        $this->actingAs($sales)->patch(route('admin.quotes.update', $q), ['status' => 'survey_booked'])->assertSessionHasErrors('survey_at');
        $this->patch(route('admin.quotes.update', $q), ['status' => 'survey_booked', 'assigned_to' => $sales->id, 'survey_at' => today()->addDays(3)->format('Y-m-d').'T10:30'])->assertSessionHasNoErrors();
        $q->refresh();
        $this->assertSame(QuoteStatus::SurveyBooked, $q->status);
        $this->assertSame($sales->id, $q->assigned_to);
        $this->assertSame('10:30', $q->survey_at?->format('H:i'));
        $this->assertFalse($q->isUnbooked());

        $this->patch(route('admin.quotes.update', $q), ['status' => 'quoted', 'assigned_to' => $sales->id, 'survey_at' => $q->survey_at?->format('Y-m-d\TH:i'), 'quoted_value' => '48000'])->assertSessionHasNoErrors();
        $this->assertSame('48000.00', $q->refresh()->quoted_value);
        $this->assertNotNull($q->quoted_at);
        // Only people who work quotes can own one.
        $this->patch(route('admin.quotes.update', $q), ['status' => 'quoted', 'assigned_to' => $this->staff(Role::Hr)->id, 'quoted_value' => '1'])->assertSessionHasErrors('assigned_to');
        $this->assertStringContainsString('Quoted value set to €48,000 a year', $q->notes()->pluck('body')->implode(' '));
    }

    public function test_operations_updates_a_ticket_and_the_client_hears(): void
    {
        Notification::fake();
        $ops = $this->staff(Role::Operations);
        $t = ServiceTicket::factory()->create(['reporter_email' => 'site@example.ie']);
        $this->actingAs($ops)->patch(route('admin.tickets.update', $t), ['status' => 'acknowledged', 'assigned_to' => $ops->id, 'client_update' => 'Supervisor on site at 6 pm.'])->assertRedirect();
        $t->refresh();
        $this->assertSame(TicketStatus::Acknowledged, $t->status);
        $this->assertNotNull($t->responded_at);
        $this->assertSame(1, $t->notes()->where('kind', 'client')->count());
        Notification::assertSentOnDemand(TicketUpdated::class, fn ($n, $c, $to) => $to->routes['mail'] === ['site@example.ie']);

        $this->patch(route('admin.tickets.update', $t), ['status' => 'resolved', 'assigned_to' => $ops->id])->assertRedirect();
        $this->assertNotNull($t->refresh()->resolved_at);
        $this->patch(route('admin.tickets.update', $t), ['status' => 'in_progress', 'assigned_to' => $ops->id]);
        $this->assertNull($t->refresh()->resolved_at, 'Reopening restarts the resolution clock');
    }

    public function test_client_links_are_created_emailed_and_withdrawn(): void
    {
        Notification::fake();
        $ops = $this->staff(Role::Operations);
        $this->actingAs($ops)->post(route('admin.accounts.store'), ['organisation' => 'Sample Campus', 'site_name' => 'Block A', 'region' => 'dublin', 'contact_email' => 'fm@example.ie', 'services' => ['cleaning', 'nonsense']])
            ->assertSessionHasErrors('services.1');
        $this->post(route('admin.accounts.store'), ['organisation' => 'Sample Campus', 'site_name' => 'Block A', 'region' => 'dublin', 'contact_email' => 'fm@example.ie', 'services' => ['cleaning']])->assertRedirect();
        $a = ClientAccount::sole();
        $this->assertSame(Region::Dublin, $a->region);
        $this->assertFalse($a->linkIsLive());

        $res = $this->post(route('admin.accounts.share', $a), ['days' => 90, 'email' => '1'])->assertSessionHas('portal_link');
        $link = (string) session('portal_link');
        Notification::assertSentOnDemand(PortalLinkShared::class, fn ($n, $c, $to) => $to->routes['mail'] === ['fm@example.ie']);
        $this->get(route('admin.accounts.show', $a))->assertSee('Copy link');
        $this->post(route('admin.accounts.share', $a), ['days' => 7])->assertSessionHasErrors('days');

        auth()->logout();
        $this->get($link)->assertOk();
        $this->actingAs($ops)->post(route('admin.accounts.revoke', $a))->assertRedirect();
        auth()->logout();
        $this->get($link)->assertForbidden();
        unset($res);
    }

    public function test_job_postings_crud(): void
    {
        $this->actingAs($this->staff(Role::Hr));
        $form = ['title' => 'Night security officer', 'role_type' => 'security_officer', 'region' => 'limerick', 'employment_type' => 'full_time', 'status' => 'open', 'location' => 'Limerick', 'hours' => 'Nights', 'openings' => 2, 'summary' => 'Static guarding.'];
        $this->post(route('admin.jobs.store'), $form)->assertRedirect(route('admin.jobs.index'));
        $job = JobPosting::sole();
        $this->assertSame('night-security-officer', $job->slug);
        $this->assertSame(Region::Limerick, $job->region);
        $this->put(route('admin.jobs.update', $job), ['status' => 'closed'] + $form)->assertRedirect();
        $this->get('/careers/night-security-officer')->assertNotFound();
    }

    public function test_changing_a_licence_expiry_rearms_its_reminders(): void
    {
        $s = StaffMember::factory()->expiringIn(10)->create(['psa_reminder_stage' => StaffMember::STAGE_EXPIRING]);
        $this->actingAs($this->staff(Role::Hr))->put(route('admin.staff.update', $s), [
            'employee_no' => $s->employee_no, 'first_name' => $s->first_name, 'last_name' => $s->last_name, 'role_type' => 'security_officer', 'region' => 'galway',
            'psa_licence_number' => $s->psa_licence_number, 'psa_licence_expires_on' => today()->addYears(3)->format('Y-m-d'), 'vetting_status' => 'cleared', 'active' => '1',
        ])->assertRedirect(route('admin.staff.show', $s));
        $this->assertSame(StaffMember::STAGE_NONE, $s->refresh()->psa_reminder_stage);
        $this->assertStringContainsString('reminders reset', $s->notes()->pluck('body')->implode(' '));
        // A security officer cannot be saved without a licence number.
        $this->put(route('admin.staff.update', $s), ['employee_no' => $s->employee_no, 'first_name' => 'A', 'last_name' => 'B', 'role_type' => 'security_officer', 'region' => 'galway', 'vetting_status' => 'cleared'])
            ->assertSessionHasErrors('psa_licence_number');
    }

    public function test_contact_form_creates_an_enquiry_for_sales(): void
    {
        Notification::fake();
        $this->post('/contact', ['name' => 'Seán Ryan', 'email' => 'S@Example.ie', 'topic' => 'services', 'message' => 'Do you clean after an office fit-out?'])->assertRedirect('/thanks');
        $e = Enquiry::sole();
        $this->assertSame('s@example.ie', $e->email);
        $this->assertStringStartsWith('EN-', $e->reference);
        $this->post('/contact', ['name' => 'x', 'email' => 'bad', 'topic' => 'nope', 'message' => 'short'])->assertSessionHasErrors(['email', 'topic', 'message']);
        $this->actingAs($this->staff(Role::Sales))->patch(route('admin.enquiries.update', $e), ['status' => 'answered', 'body' => 'Rang back.'])->assertRedirect();
        $this->assertSame('answered', $e->refresh()->status->value);
    }

    public function test_forms_are_rate_limited(): void
    {
        for ($i = 0; $i < 40; $i++) {
            $this->post('/contact', []);
        }
        $this->post('/contact', [])->assertStatus(429);
    }
}
