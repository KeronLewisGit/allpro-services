<?php

namespace Tests\Feature;

use App\Models\ClientAccount;
use App\Models\JobApplication;
use App\Models\QuoteRequest;
use App\Models\ServiceTicket;
use App\Support\HeldUploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** A form that comes back with an error keeps the files that were fine; nobody attaches them twice. */
class HeldUploadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
    }

    /** @return array<string, mixed> */
    private function quote(array $overrides = []): array
    {
        return array_merge([
            'services' => ['cleaning'], 'sector' => 'retail', 'organisation' => 'Sample Retail', 'contact_name' => 'Eoin Walsh', 'email' => 'e@example.ie',
            'phone' => '021 123 4567', 'site_name' => 'Shop', 'site_address' => 'Main Street', 'town' => 'Mallow', 'county' => 'Cork', 'site_count' => 1,
            'survey_on' => today()->addWeekdays(4)->format('Y-m-d'), 'survey_window' => 'any', 'consent' => '1',
        ], $overrides);
    }

    public function test_quote_plans_survive_a_validation_error(): void
    {
        $this->from('/quote')->post('/quote', $this->quote(['organisation' => '', 'files' => [$this->pdf('plan.pdf'), $this->pdf('spec.pdf')]]))->assertSessionHasErrors('organisation');
        $this->assertCount(2, Storage::disk('local')->allFiles(HeldUploads::DIR));
        $this->get('/quote')->assertSee('Attached:')->assertSee('plan.pdf, spec.pdf');

        $this->post('/quote', $this->quote())->assertRedirect('/thanks');
        $q = QuoteRequest::sole();
        $this->assertSame(['plan.pdf', 'spec.pdf'], $q->files()->orderBy('original_name')->pluck('original_name')->all());
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
    }

    public function test_a_rejected_file_is_not_held_and_a_new_choice_replaces_the_held_one(): void
    {
        $this->post('/quote', $this->quote(['organisation' => '', 'files' => [UploadedFile::fake()->create('virus.exe', 10)]]))->assertSessionHasErrors(['organisation', 'files.0']);
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
        $this->post('/quote', $this->quote(['organisation' => '', 'files' => [$this->pdf('old.pdf')]]));
        $this->post('/quote', $this->quote(['organisation' => '', 'files' => [$this->pdf('new.pdf')]]));
        $this->assertSame(['new.pdf'], HeldUploads::names('quote', 'files'));
    }

    public function test_a_cv_survives_a_validation_error(): void
    {
        $form = ['role_type' => 'cleaning_operative', 'region' => 'dublin', 'first_name' => 'Ewa', 'last_name' => 'Kowalska', 'phone' => '085 111 2222',
            'experience' => 'none', 'right_to_work' => 'citizen', 'availability' => ['mon-late'], 'consent' => '1'];
        $this->post('/careers/apply', array_merge($form, ['phone' => '', 'cv' => $this->pdf('cv.pdf')]))->assertSessionHasErrors('phone');
        $this->assertSame(['cv.pdf'], HeldUploads::names('application', 'cv'));
        $this->post('/careers/apply', $form)->assertRedirect('/thanks');
        Storage::disk('local')->assertExists((string) JobApplication::sole()->cv_path);
    }

    public function test_ticket_photos_survive_a_validation_error(): void
    {
        $a = ClientAccount::factory()->create();
        $url = URL::temporarySignedRoute('portal.store', now()->addDay(), ['account' => $a->public_id, 'key' => $a->access_key]);
        $form = ['type' => 'request', 'category' => 'washroom', 'priority' => 'normal', 'reporter_name' => 'Gráinne', 'reporter_email' => 'g@example.ie', 'description' => 'Soap dispensers empty on floor 2.'];
        $this->post($url, array_merge($form, ['description' => '', 'photos' => [UploadedFile::fake()->image('a.jpg')]]))->assertSessionHasErrors('description');
        $this->assertSame(['a.jpg'], HeldUploads::names('ticket', 'photos'));
        $this->post($url, $form)->assertRedirect();
        $this->assertCount(1, ServiceTicket::sole()->photos);
    }
}
