<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\ClientAccount;
use App\Models\ServiceTicket;
use App\Notifications\NewSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** The client side: a signed, expiring, revocable link per site. */
class ServiceDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
    }

    /** @return array<string, mixed> */
    private function ticket(array $overrides = []): array
    {
        return array_merge(['type' => 'complaint', 'category' => 'cleaning', 'priority' => 'high', 'reporter_name' => 'Orla Walsh',
            'reporter_email' => 'orla@example.ie', 'description' => 'Bins in the car park not emptied since Friday.'], $overrides);
    }

    private function postUrl(ClientAccount $a): string
    {
        return URL::temporarySignedRoute('portal.store', $a->link_expires_at ?? now()->addDay(), ['account' => $a->public_id, 'key' => $a->access_key]);
    }

    public function test_a_client_opens_their_link_and_logs_a_ticket_with_sla_clocks(): void
    {
        config(['allpro.notify.servicedesk' => 'ops@example.com']);
        $a = ClientAccount::factory()->create(['site_name' => 'Block A']);
        $url = $a->shareLink(90);
        $this->get($url)->assertOk()->assertSee('Block A')->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->freezeTime();
        $this->from($url)->post($this->postUrl($a->refresh()), $this->ticket())->assertRedirect($url)->assertSessionHas('logged');
        $t = ServiceTicket::sole();
        $this->assertStringStartsWith('SR-', $t->reference);
        $this->assertSame(TicketStatus::New, $t->status);
        $this->assertSame(now()->addHours(TicketPriority::High->responseHours())->format('Y-m-d H:i:s'), $t->response_due_at->format('Y-m-d H:i:s'));
        $this->assertSame(now()->addHours(TicketPriority::High->resolveHours())->format('Y-m-d H:i:s'), $t->resolve_due_at->format('Y-m-d H:i:s'));
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $c, $to) => $to->routes['mail'] === ['ops@example.com']);
    }

    public function test_links_must_be_signed_current_and_not_withdrawn(): void
    {
        $a = ClientAccount::factory()->create();
        $url = $a->shareLink(30);
        $this->get(route('portal.show', ['account' => $a->public_id, 'key' => $a->access_key]))->assertForbidden(); // unsigned
        $this->get(URL::temporarySignedRoute('portal.show', now()->addDay(), ['account' => $a->public_id, 'key' => str_repeat('x', 40)]))->assertForbidden(); // wrong key

        $this->travel(31)->days();
        $this->get($url)->assertForbidden(); // expired
        $this->travelBack();

        $fresh = $a->refresh()->shareLink(90);
        $this->get($fresh)->assertOk();
        $a->revoke();
        $this->get($fresh)->assertForbidden();
        $this->post($this->postUrl($a->refresh()), $this->ticket())->assertForbidden();
        $this->assertSame(0, ServiceTicket::count());
    }

    public function test_a_reporter_must_leave_a_way_to_reach_them_and_photos_are_private(): void
    {
        $a = ClientAccount::factory()->create();
        $this->post($this->postUrl($a), $this->ticket(['reporter_email' => '', 'reporter_phone' => '']))->assertSessionHasErrors(['reporter_email', 'reporter_phone']);
        $this->post($this->postUrl($a), $this->ticket(['priority' => 'whenever', 'description' => 'short']))->assertSessionHasErrors(['priority', 'description']);
        $this->post($this->postUrl($a), $this->ticket(['reporter_email' => '', 'reporter_phone' => '087 555 0101', 'photos' => [UploadedFile::fake()->image('bins.jpg')]]))->assertRedirect();
        $t = ServiceTicket::sole();
        $this->assertCount(1, $t->photos);
        $this->assertStringStartsWith('tickets/'.$t->id.'/', $t->photos->first()->path);
    }

    public function test_clients_see_their_own_tickets_and_client_updates_but_never_internal_notes(): void
    {
        $a = ClientAccount::factory()->create();
        $other = ClientAccount::factory()->create();
        $mine = ServiceTicket::factory()->create(['client_account_id' => $a->id, 'description' => 'Our own request about the lift lobby.']);
        ServiceTicket::factory()->create(['client_account_id' => $other->id, 'description' => 'Someone else entirely, another client.']);
        $mine->log('Supervisor is on it this evening.', 'client');
        $mine->log('Internal: cleaner was off sick, cover from Tuam.', 'note');
        $this->get($a->shareLink(30))->assertOk()->assertSee('Our own request')->assertSee('Supervisor is on it this evening.')
            ->assertDontSee('Someone else entirely')->assertDontSee('cleaner was off sick');
    }

    public function test_sla_clock_states(): void
    {
        $this->freezeTime();
        $urgent = ServiceTicket::factory()->priority(TicketPriority::Urgent)->loggedAgo(50)->create();
        $this->assertSame('due-soon', $urgent->clock('response'));
        $late = ServiceTicket::factory()->priority(TicketPriority::Urgent)->loggedAgo(90)->create();
        $this->assertSame('overdue', $late->clock('response'));
        $calm = ServiceTicket::factory()->priority(TicketPriority::Low)->loggedAgo(10)->create();
        $this->assertSame('on-track', $calm->clock('response'));
        $this->assertEqualsCanonicalizing([$urgent->id, $late->id], ServiceTicket::atRisk()->pluck('id')->all());

        $late->claimStatus(TicketStatus::Acknowledged);
        $this->assertSame('breached', $late->refresh()->clock('response'));
        $urgent->claimStatus(TicketStatus::Resolved);
        $this->assertSame('met', $urgent->refresh()->clock('response'));
        $this->assertSame('met', $urgent->clock('resolve'));
        $this->assertNotContains($urgent->id, ServiceTicket::atRisk()->pluck('id')->all());
    }
}
