<?php

namespace Tests\Feature;

use App\Enums\Region;
use App\Enums\Role;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\NewSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A Wednesday, so "two working days" and weekends are predictable.
        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00', 'Europe/Dublin'));
        Notification::fake();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function quote(array $overrides = []): array
    {
        return array_merge([
            'services' => ['cleaning', 'washroom'], 'sector' => 'corporate', 'organisation' => 'Sample Software Ltd', 'contact_name' => 'Niamh Kelly',
            'email' => 'Facilities@Example.ie', 'phone' => '091 123 456', 'site_name' => 'Head office', 'site_address' => 'Unit 4, Sample Business Park',
            'town' => 'Ballybrit', 'county' => 'Galway', 'eircode' => 'h91x2y3', 'site_count' => 1, 'floor_area_m2' => 2400,
            'survey_on' => '2026-11-09', 'survey_window' => 'morning', 'consent' => '1',
        ], $overrides);
    }

    public function test_a_request_is_routed_to_the_sales_owner_for_the_county(): void
    {
        config(['allpro.notify.quotes' => 'sales@example.com']);
        User::factory()->role(Role::Sales)->create(['region' => Region::Galway, 'name' => 'Galway Sales']);
        $cork = User::factory()->role(Role::Sales)->create(['region' => Region::Cork, 'name' => 'Cork Sales']);
        $this->post('/quote', $this->quote(['county' => 'Kerry']))->assertRedirect('/thanks');
        $q = QuoteRequest::sole();
        $this->assertSame(Region::Cork, $q->region);
        $this->assertSame($cork->id, $q->assigned_to);
        $this->assertSame('facilities@example.ie', $q->email);
        $this->assertSame('H91 X2Y3', $q->eircode);
        $this->assertStringStartsWith('QR-', $q->reference);
        $this->assertSame(['cleaning', 'washroom'], $q->services);
        $this->assertStringContainsString('Assigned to Cork Sales', $q->notes()->pluck('body')->implode(' '));
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $c, $to) => $to->routes['mail'] === ['sales@example.com']);
    }

    public function test_without_a_regional_owner_the_request_waits_unassigned(): void
    {
        $this->post('/quote', $this->quote())->assertRedirect('/thanks');
        $this->assertNull(QuoteRequest::sole()->assigned_to);
        $this->assertStringContainsString('assign one', QuoteRequest::sole()->notes()->pluck('body')->implode(' '));
    }

    public function test_survey_dates_are_weekdays_at_least_two_working_days_out(): void
    {
        $this->post('/quote', $this->quote(['survey_on' => '2026-11-05']))->assertSessionHasErrors('survey_on');
        $this->post('/quote', $this->quote(['survey_on' => '2026-11-07']))->assertSessionHasErrors(['survey_on' => 'Site surveys are carried out Monday to Friday. Choose a weekday.']);
        $this->post('/quote', $this->quote(['survey_on' => '2027-06-01']))->assertSessionHasErrors('survey_on');
        $this->post('/quote', $this->quote(['survey_on' => '2026-11-06']))->assertRedirect('/thanks');
    }

    public function test_validation_of_services_county_eircode_and_tenders(): void
    {
        $this->post('/quote', $this->quote(['services' => ['rocket-science'], 'county' => 'Narnia', 'eircode' => '12345', 'consent' => null]))
            ->assertSessionHasErrors(['services.0', 'county', 'eircode', 'consent']);
        $this->post('/quote', $this->quote(['is_tender' => '1']))->assertSessionHasErrors('tender_closes_on');
        $this->post('/quote', $this->quote(['is_tender' => '1', 'tender_reference' => 'RFT-1', 'tender_closes_on' => '2026-11-20', 'eircode' => '']))->assertRedirect('/thanks');
        $this->assertTrue(QuoteRequest::sole()->is_tender);
    }

    public function test_plans_are_stored_privately_per_request(): void
    {
        $plan = UploadedFile::fake()->createWithContent('plan.pdf', "%PDF-1.4\n%%EOF\n");
        $this->post('/quote', $this->quote(['files' => [$plan]]))->assertRedirect('/thanks');
        $q = QuoteRequest::sole();
        $this->assertCount(1, $q->files);
        $this->assertStringStartsWith('quotes/'.$q->id.'/', $q->files->first()->path);
        Storage::disk('local')->assertExists($q->files->first()->path);
        $this->post('/quote', $this->quote(['files' => [UploadedFile::fake()->create('x.exe', 10)]]))->assertSessionHasErrors('files.0');
    }

    public function test_bots_are_thanked_and_ignored(): void
    {
        $this->post('/quote', $this->quote(['website' => 'x']))->assertRedirect('/thanks');
        $this->assertSame(0, QuoteRequest::count());
    }
}
