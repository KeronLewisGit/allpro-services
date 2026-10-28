<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ClientAccount;
use App\Models\JobApplication;
use App\Models\QuoteRequest;
use App\Models\ServiceTicket;
use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/admin', '/admin/tickets', '/admin/quotes', '/admin/applications', '/admin/staff', '/admin/users'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_login_and_inactive_accounts(): void
    {
        $user = User::factory()->create(['email' => 'ops@example.com']);
        $this->post('/login', ['email' => 'OPS@example.com', 'password' => 'password'])->assertRedirect('/admin');
        $user->update(['active' => false]);
        $this->app['auth']->forgetGuards(); // the next request loads the user afresh, as a real one would
        $this->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'ops@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    }

    /** @return array<string, array{Role, array<string, int>}> */
    public static function matrix(): array
    {
        $urls = ['/admin/tickets', '/admin/clients', '/admin/quotes', '/admin/enquiries', '/admin/applications', '/admin/jobs', '/admin/staff', '/admin/users'];
        $row = fn (array $ok) => array_combine($urls, array_map(fn ($u) => in_array($u, $ok, true) ? 200 : 403, $urls));

        return [
            'admin' => [Role::Admin, $row($urls)],
            'sales' => [Role::Sales, $row(['/admin/quotes', '/admin/enquiries'])],
            'hr' => [Role::Hr, $row(['/admin/applications', '/admin/jobs', '/admin/staff'])],
            'operations' => [Role::Operations, $row(['/admin/tickets', '/admin/clients'])],
        ];
    }

    /** @param array<string, int> $expect */
    #[DataProvider('matrix')]
    public function test_each_role_sees_only_its_areas(Role $role, array $expect): void
    {
        $this->actingAs($this->staff($role));
        $this->get('/admin')->assertOk();
        foreach ($expect as $url => $status) {
            $this->get($url)->assertStatus($status);
        }
    }

    public function test_records_are_closed_to_other_roles(): void
    {
        $ticket = ServiceTicket::factory()->create();
        $quote = QuoteRequest::factory()->create();
        $app = JobApplication::factory()->create();
        $staff = StaffMember::factory()->create();
        $checks = [
            [route('admin.tickets.show', $ticket), Role::Operations],
            [route('admin.accounts.show', $ticket->account), Role::Operations],
            [route('admin.quotes.show', $quote), Role::Sales],
            [route('admin.applications.show', $app), Role::Hr],
            [route('admin.staff.show', $staff), Role::Hr],
        ];
        foreach (Role::cases() as $role) {
            $user = $this->staff($role);
            foreach ($checks as [$url, $owner]) {
                $this->actingAs($user)->get($url)->assertStatus($role === $owner || $role === Role::Admin ? 200 : 403);
            }
        }
        // Writes too, not just reads.
        $this->actingAs($this->staff(Role::Sales))->patch(route('admin.tickets.update', $ticket), ['status' => 'closed'])->assertForbidden();
        $this->actingAs($this->staff(Role::Operations))->patch(route('admin.applications.checks', $app), ['vetting_status' => 'cleared'])->assertForbidden();
        $this->actingAs($this->staff(Role::Hr))->post(route('admin.accounts.share', $ticket->account), ['days' => 30])->assertForbidden();
        $this->assertSame('new', $ticket->refresh()->status->value);
    }

    public function test_private_files_open_only_for_their_area_and_each_opening_is_logged(): void
    {
        Storage::fake('local');
        $app = JobApplication::factory()->create(['cv_path' => 'cvs/a.pdf', 'cv_name' => 'cv.pdf', 'cv_size' => 10]);
        Storage::disk('local')->put('cvs/a.pdf', 'x');
        $quote = QuoteRequest::factory()->create();
        Storage::disk('local')->put('quotes/'.$quote->id.'/plan.pdf', 'x');
        $file = $quote->files()->create(['path' => 'quotes/'.$quote->id.'/plan.pdf', 'original_name' => 'plan.pdf', 'mime' => 'application/pdf', 'size' => 1]);
        $ticket = ServiceTicket::factory()->create();
        $photo = $ticket->photos()->create(['path' => UploadedFile::fake()->image('p.jpg')->store('tickets/'.$ticket->id, 'local'), 'original_name' => 'p.jpg', 'mime' => 'image/jpeg', 'size' => 1]);

        $this->actingAs($this->staff(Role::Sales))->get(route('admin.applications.cv', $app))->assertForbidden();
        $this->actingAs($this->staff(Role::Hr))->get(route('admin.applications.cv', $app))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->staff(Role::Hr))->get(route('admin.quotes.file', [$quote, $file]))->assertForbidden();
        $this->actingAs($this->staff(Role::Sales))->get(route('admin.quotes.file', [$quote, $file]))->assertOk();
        $this->actingAs($this->staff(Role::Sales))->get(route('admin.tickets.photo', [$ticket, $photo]))->assertForbidden();
        $this->actingAs($this->staff(Role::Operations))->get(route('admin.tickets.photo', [$ticket, $photo]))->assertOk();
        // A file id from another record never opens through this one.
        $other = QuoteRequest::factory()->create();
        $this->actingAs($this->staff(Role::Sales))->get(route('admin.quotes.file', [$other, $file]))->assertNotFound();

        $this->assertSame(1, $app->notes()->where('kind', 'file')->count());
        $this->assertSame(1, $quote->notes()->where('kind', 'file')->count());
        $this->assertSame(1, $ticket->notes()->where('kind', 'file')->count());
    }

    public function test_csv_exports_are_formula_safe(): void
    {
        QuoteRequest::factory()->create(['organisation' => '=HYPERLINK("x")']);
        $csv = $this->actingAs($this->staff(Role::Sales))->get(route('admin.quotes.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        ServiceTicket::factory()->create(['client_account_id' => ClientAccount::factory()->create(['organisation' => '+cmd'])->id]);
        $csv = $this->actingAs($this->staff(Role::Operations))->get(route('admin.tickets.export'))->streamedContent();
        $this->assertStringContainsString("'+cmd", $csv);
        $this->actingAs($this->staff(Role::Hr))->get(route('admin.applications.export'))->assertOk();
        $this->actingAs($this->staff(Role::Hr))->get(route('admin.staff.export'))->assertOk();
    }

    public function test_staff_accounts_are_admin_only_and_admins_keep_their_access(): void
    {
        $admin = $this->staff(Role::Admin);
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Cork Sales', 'email' => 'Cork@x.ie', 'role' => 'sales', 'region' => 'cork', 'password' => 'long-password'])->assertRedirect();
        $u = User::where('email', 'cork@x.ie')->firstOrFail();
        $this->assertSame('cork', $u->region?->value);
        $this->patch(route('admin.users.update', $admin), ['role' => 'hr', 'active' => '1'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Dup', 'email' => 'CORK@X.IE', 'role' => 'hr', 'password' => 'long-password'])->assertSessionHasErrors('email');
        $this->actingAs($this->staff(Role::Hr))->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_every_admin_screen_renders_with_demo_data(): void
    {
        $this->seed(DemoSeeder::class);
        $admin = User::where('email', 'admin@allpro.test')->firstOrFail();
        $this->actingAs($admin);
        $urls = ['/admin', '/admin/tickets', '/admin/tickets?risk=1', '/admin/clients', '/admin/quotes', '/admin/quotes?booking=1&owner=none', '/admin/applications',
            '/admin/applications?shift=night&region=dublin', '/admin/jobs', '/admin/jobs/create', '/admin/staff', '/admin/staff?due=1', '/admin/staff/create', '/admin/enquiries', '/admin/users'];
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        foreach ([ServiceTicket::first(), ClientAccount::first(), QuoteRequest::first(), JobApplication::first(), StaffMember::first()] as $m) {
            $route = match (true) {
                $m instanceof ServiceTicket => 'admin.tickets.show',
                $m instanceof ClientAccount => 'admin.accounts.show',
                $m instanceof QuoteRequest => 'admin.quotes.show',
                $m instanceof JobApplication => 'admin.applications.show',
                default => 'admin.staff.show',
            };
            $this->get(route($route, $m))->assertOk();
        }
        $this->get(route('admin.staff.edit', StaffMember::first()))->assertOk();
        foreach (['sales', 'hr', 'operations'] as $role) {
            $this->actingAs(User::where('email', $role.'@allpro.test')->firstOrFail())->get('/admin')->assertOk();
        }
    }
}
