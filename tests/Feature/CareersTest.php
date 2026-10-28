<?php

namespace Tests\Feature;

use App\Enums\Region;
use App\Enums\RoleType;
use App\Enums\VettingStatus;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Notifications\NewSubmission;
use App\Notifications\SubmissionReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
    }

    /** @return array<string, mixed> */
    private function application(array $overrides = []): array
    {
        return array_merge([
            'role_type' => 'cleaning_operative', 'region' => 'galway', 'first_name' => 'Aoife', 'last_name' => 'Byrne', 'phone' => '087 123 4567',
            'experience' => 'under_1', 'right_to_work' => 'citizen', 'availability' => ['mon-early', 'tue-early', 'sat-night'], 'consent' => '1',
        ], $overrides);
    }

    /** @return array<string, string> */
    private function psa(int $days = 300): array
    {
        return ['psa_licence_number' => '1234567', 'psa_licence_expires_on' => today()->addDays($days)->format('Y-m-d')];
    }

    public function test_a_cleaner_can_apply_with_a_phone_number_and_shifts(): void
    {
        config(['allpro.notify.careers' => 'hr@example.com']);
        $this->post('/careers/apply', $this->application())->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertSame(sprintf('JA-%s-%05d', now()->format('y'), $a->id), $a->reference);
        $this->assertSame(['mon-early', 'tue-early', 'sat-night'], $a->availability);
        $this->assertSame(VettingStatus::NotStarted, $a->vetting_status);
        $this->assertNull($a->cv_path);
        $this->assertSame('n/a', $a->psaState());
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $c, $to) => $to->routes['mail'] === ['hr@example.com']);
    }

    public function test_availability_is_required_and_junk_keys_are_dropped(): void
    {
        $this->post('/careers/apply', $this->application(['availability' => []]))->assertSessionHasErrors(['availability' => 'Tick at least one shift you can work.']);
        $this->post('/careers/apply', $this->application(['availability' => ['mon-early', 'xmas-party', ['nested']]]))->assertRedirect('/thanks');
        $this->assertSame(['mon-early'], JobApplication::sole()->availability);
    }

    public function test_security_officers_need_a_current_psa_licence(): void
    {
        $this->post('/careers/apply', $this->application(['role_type' => 'security_officer']))
            ->assertSessionHasErrors(['psa_licence_number', 'psa_licence_expires_on']);
        $this->post('/careers/apply', $this->application(['role_type' => 'security_officer'] + $this->psa(-1)))
            ->assertSessionHasErrors(['psa_licence_expires_on' => 'That PSA licence has expired. Renew it with the PSA before applying for a security role.']);
        $this->post('/careers/apply', $this->application(['role_type' => 'security_officer', 'psa_licence_number' => 'ABC'] + array_slice($this->psa(), 1)))
            ->assertSessionHasErrors('psa_licence_number');
        $this->post('/careers/apply', $this->application(['role_type' => 'security_officer', 'psa_licence_number' => '123 4567', 'psa_licence_expires_on' => today()->addDays(300)->format('Y-m-d')]))
            ->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertSame('1234567', $a->psa_licence_number);
        $this->assertSame('valid', $a->psaState());
    }

    public function test_supervisors_may_skip_the_licence_and_cleaners_cannot_store_one(): void
    {
        $this->post('/careers/apply', $this->application(['role_type' => 'supervisor']))->assertRedirect('/thanks');
        $this->post('/careers/apply', $this->application(['first_name' => 'Cian'] + $this->psa()))->assertRedirect('/thanks');
        $cleaner = JobApplication::where('first_name', 'Cian')->sole();
        $this->assertNull($cleaner->psa_licence_number);
    }

    public function test_garda_vetting_is_never_collected_on_the_public_form(): void
    {
        $this->post('/careers/apply', $this->application([
            'vetting_status' => 'cleared', 'vetting_updated_at' => now()->toDateTimeString(), 'rtw_checked_at' => now()->toDateTimeString(),
            'psa_checked_at' => now()->toDateTimeString(), 'status' => 'hired',
            'vetting_form' => UploadedFile::fake()->create('nvb1.pdf', 20, 'application/pdf'),
            'passport' => UploadedFile::fake()->image('passport.jpg'),
        ]))->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertSame(VettingStatus::NotStarted, $a->vetting_status);
        $this->assertNull($a->rtw_checked_at);
        $this->assertNull($a->psa_checked_at);
        $this->assertSame('new', $a->status->value);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->get('/careers/apply')->assertDontSee('name="vetting', false)->assertSee('e-vetting');
    }

    public function test_the_opening_decides_the_role_and_region(): void
    {
        $job = JobPosting::factory()->security()->create(['region' => Region::Cork]);
        // Claims cleaning in Galway, but applies to a Cork security post: the PSA rule follows the job.
        $this->post('/careers/apply', $this->application(['job_posting_id' => $job->id]))->assertSessionHasErrors('psa_licence_number');
        $this->post('/careers/apply', $this->application(['job_posting_id' => $job->id] + $this->psa()))->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertSame(RoleType::SecurityOfficer, $a->role_type);
        $this->assertSame(Region::Cork, $a->region);
        $this->get('/careers/apply?job='.$job->slug)->assertOk()->assertSee($job->title);
    }

    public function test_a_closed_opening_gets_a_plain_message(): void
    {
        $closed = JobPosting::factory()->create(['closes_on' => today()->subDay()]);
        $this->post('/careers/apply', $this->application(['job_posting_id' => $closed->id]))
            ->assertSessionHasErrors(['job_posting_id' => 'That opening has closed. Choose another opening, or send a general application.']);
    }

    public function test_a_cv_is_optional_and_stored_privately(): void
    {
        $this->post('/careers/apply', $this->application(['email' => 'A@Example.ie', 'cv' => UploadedFile::fake()->create('cv.pdf', 300, 'application/pdf')]))->assertRedirect('/thanks');
        $a = JobApplication::sole();
        Storage::disk('local')->assertExists((string) $a->cv_path);
        $this->assertStringStartsWith('cvs/', (string) $a->cv_path);
        $this->assertSame('a@example.ie', $a->email);
        Notification::assertSentOnDemand(SubmissionReceived::class, fn ($n, $c, $to) => $to->routes['mail'] === ['a@example.ie']);
        $this->post('/careers/apply', $this->application(['cv' => UploadedFile::fake()->create('cv.exe', 10)]))->assertSessionHasErrors('cv');
    }

    public function test_bots_get_a_thank_you_and_nothing_is_saved(): void
    {
        $this->post('/careers/apply', $this->application(['website' => 'http://spam.example']))->assertRedirect('/thanks');
        $this->assertSame(0, JobApplication::count());
    }
}
