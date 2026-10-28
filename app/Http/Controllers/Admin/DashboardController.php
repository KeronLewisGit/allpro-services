<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\Area;
use App\Enums\EnquiryStatus;
use App\Enums\QuoteStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\QuoteRequest;
use App\Models\ServiceTicket;
use App\Models\StaffMember;
use App\Support\Notify;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Leads with the one number the signed-in role must act on: service tickets breaching their
 * SLA (operations, admin), surveys requested this week and not yet booked (sales) or PSA
 * licences expired or expiring (HR). Tiles for the role's other areas follow.
 */
class DashboardController extends Controller
{
    use Concerns;

    public function __invoke(Request $request): View
    {
        $user = $this->user($request);
        $headline = match (true) {
            $user->works(Area::ServiceDesk) => $this->deskHeadline(),
            $user->works(Area::Quotes) => $this->quoteHeadline(),
            default => $this->licenceHeadline(),
        };

        $tiles = [];
        if ($user->works(Area::ServiceDesk)) {
            $tiles[] = ['label' => 'Open service tickets', 'value' => ServiceTicket::open()->count(), 'note' => ServiceTicket::open()->where('priority', TicketPriority::Urgent)->count().' urgent', 'href' => route('admin.tickets.index')];
            $tiles[] = ['label' => 'Complaints, last 30 days', 'value' => ServiceTicket::where('type', TicketType::Complaint)->where('created_at', '>=', now()->subDays(30))->count(),
                'previous' => ServiceTicket::where('type', TicketType::Complaint)->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count(), 'period' => 'vs previous 30 days', 'upIsGood' => false, 'href' => route('admin.tickets.index', ['type' => 'complaint'])];
            $answered = ServiceTicket::whereNotNull('responded_at')->where('created_at', '>=', now()->subDays(30))->get(['responded_at', 'response_due_at']);
            $met = $answered->filter(fn (ServiceTicket $t) => $t->responded_at !== null && $t->responded_at->lte($t->response_due_at))->count();
            $tiles[] = ['label' => 'Responded within SLA, 30 days', 'value' => $answered->isEmpty() ? 0 : (int) round($met / $answered->count() * 100), 'suffix' => '%', 'note' => $met.' of '.$answered->count().' tickets', 'href' => route('admin.tickets.index')];
        }
        if ($user->works(Area::Quotes)) {
            $tiles[] = ['label' => 'Open quote requests', 'value' => QuoteRequest::open()->count(), 'note' => QuoteRequest::where('status', QuoteStatus::New)->count().' not yet booked', 'href' => route('admin.quotes.index')];
            $tiles[] = ['label' => 'Requests, last 30 days', 'value' => QuoteRequest::where('created_at', '>=', now()->subDays(30))->count(),
                'previous' => QuoteRequest::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count(), 'period' => 'vs previous 30 days', 'href' => route('admin.quotes.index')];
        }
        if ($user->works(Area::Careers)) {
            $tiles[] = ['label' => 'Applications, last 7 days', 'value' => JobApplication::where('created_at', '>=', now()->subDays(7))->count(),
                'previous' => JobApplication::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(), 'period' => 'vs previous 7 days', 'href' => route('admin.applications.index')];
            $tiles[] = ['label' => 'Waiting over '.JobApplication::REVIEW_DAYS.' days', 'value' => JobApplication::waiting()->count(), 'note' => JobPosting::open()->count().' openings posted', 'href' => route('admin.applications.index', ['waiting' => 1])];
        }
        if ($user->works(Area::Staff)) {
            $tiles[] = ['label' => 'Staff on the register', 'value' => StaffMember::where('active', true)->count(), 'note' => StaffMember::licenceDue()->count().' PSA licences to renew', 'href' => route('admin.staff.index')];
        }
        if ($user->works(Area::Enquiries)) {
            $tiles[] = ['label' => 'New enquiries', 'value' => Enquiry::where('status', EnquiryStatus::New)->count(), 'note' => 'from the contact page', 'href' => route('admin.enquiries.index', ['status' => 'new'])];
        }

        return view('admin.dashboard', [
            'headline' => $headline,
            'tiles' => $tiles,
            'pipeline' => $this->pipeline($user->works(Area::ServiceDesk) ? 'desk' : ($user->works(Area::Quotes) ? 'quotes' : 'careers')),
            'chart' => $user->works(Area::ServiceDesk) ? $this->ticketChart() : ($user->works(Area::Quotes) ? $this->surveyChart() : $this->applicationChart()),
            'notifyMissing' => $user->isAdmin() && Notify::recipients() === [],
            'notifyInvalid' => $user->isAdmin() ? Notify::invalid() : [],
        ]);
    }

    /** @return array<string, mixed> */
    private function deskHeadline(): array
    {
        $risk = ServiceTicket::atRisk()->with('account')->get()->sortBy(fn (ServiceTicket $t) => $t->activeDue()->getTimestamp())->values();
        $overdue = $risk->filter(fn (ServiceTicket $t) => $t->clock($t->activeClock()) === 'overdue')->count();

        return [
            'area' => 'servicedesk',
            'count' => $risk->count(),
            'title' => ($risk->count() === 1 ? 'ticket has' : 'tickets have').' breached or will breach the SLA within the hour',
            'body' => $risk->isEmpty()
                ? 'Every open request and complaint is inside its response and resolution times.'
                : "{$overdue} already overdue. Respond or update the client before the clock runs out.",
            'cta' => ['label' => $risk->isEmpty() ? 'Open the service desk' : 'See tickets at risk', 'href' => route('admin.tickets.index', $risk->isEmpty() ? [] : ['risk' => 1])],
            'items' => $risk->take(6)->map(fn (ServiceTicket $t) => [
                'href' => route('admin.tickets.show', $t), 'title' => $t->account->displayName(), 'meta' => $t->reference.' · '.$t->type->label().' · '.$t->priority->label(),
                'chip' => $t->slaLabel(), 'tone' => $t->slaTone(), 'icon' => 'clock',
            ])->all(),
            'list' => 'Most urgent first',
            'empty' => 'Nothing at risk.',
        ];
    }

    /** @return array<string, mixed> */
    private function quoteHeadline(): array
    {
        $due = QuoteRequest::needsBooking()->with('assignee')->orderBy('survey_on')->get();

        return [
            'area' => 'quotes',
            'count' => $due->count(),
            'title' => ($due->count() === 1 ? 'site survey requested' : 'site surveys requested').' for the next '.QuoteRequest::SURVEY_SOON_DAYS.' days '.($due->count() === 1 ? 'is' : 'are').' not booked',
            'body' => $due->isEmpty()
                ? 'Every survey requested for this week has a booking.'
                : 'Phone the contact, book the visit, and mark the request "Survey booked". The earliest is '.$due->first()->survey_on->format('l j F').'.',
            'cta' => ['label' => $due->isEmpty() ? 'Open quote requests' : 'Book these surveys', 'href' => route('admin.quotes.index', $due->isEmpty() ? [] : ['booking' => 1])],
            'items' => $due->take(6)->map(fn (QuoteRequest $q) => [
                'href' => route('admin.quotes.show', $q), 'title' => $q->organisation, 'meta' => $q->town.', Co. '.$q->county.' · '.($q->assignee->name ?? 'Unassigned'),
                'chip' => $q->surveyLabel(), 'tone' => $q->surveyTone(), 'icon' => 'calendar',
            ])->all(),
            'list' => 'Requested soonest',
            'empty' => 'Nothing to book.',
        ];
    }

    /** @return array<string, mixed> */
    private function licenceHeadline(): array
    {
        $due = StaffMember::licenceDue()->orderBy('psa_licence_expires_on')->get();
        $expired = $due->filter(fn (StaffMember $s) => $s->psaState() === 'expired')->count();

        return [
            'area' => 'staff',
            'count' => $due->count(),
            'title' => ($due->count() === 1 ? 'PSA licence has' : 'PSA licences have').' expired or expire within '.StaffMember::EXPIRY_WARNING_DAYS.' days',
            'body' => $due->isEmpty()
                ? 'Every active security officer holds a PSA licence with more than '.StaffMember::EXPIRY_WARNING_DAYS.' days to run.'
                : "{$expired} already expired: take them off security rosters until the renewal arrives. HR is emailed a reminder each morning.",
            'cta' => ['label' => 'Licences to renew', 'href' => route('admin.staff.index', ['due' => 1])],
            'items' => $due->take(6)->map(fn (StaffMember $s) => [
                'href' => route('admin.staff.show', $s), 'title' => $s->full_name, 'meta' => $s->employee_no.' · '.$s->role_type->label().' · '.$s->region->label(),
                'chip' => $s->psaState() === 'expired' ? 'Expired '.$s->psa_licence_expires_on?->format('j M') : $s->daysLeft().' days left',
                'tone' => $s->psaState() === 'expired' ? 'critical' : 'attention', 'icon' => 'shield',
            ])->all(),
            'list' => 'Expiring first',
            'empty' => 'No licences to chase.',
        ];
    }

    /** @return array{title: string, rows: list<array{label: string, count: int, href: string, tone: string}>} */
    private function pipeline(string $which): array
    {
        if ($which === 'desk') {
            return ['title' => 'Open tickets by priority', 'rows' => array_map(fn (TicketPriority $p) => [
                'label' => $p->label(), 'count' => ServiceTicket::open()->where('priority', $p)->count(), 'href' => route('admin.tickets.index', ['priority' => $p->value]), 'tone' => $p->tone(),
            ], TicketPriority::cases())];
        }
        if ($which === 'quotes') {
            return ['title' => 'Quote pipeline', 'rows' => array_map(fn (QuoteStatus $s) => [
                'label' => $s->label(), 'count' => QuoteRequest::where('status', $s)->count(), 'href' => route('admin.quotes.index', ['status' => $s->value]), 'tone' => $s->tone(),
            ], QuoteStatus::cases())];
        }

        return ['title' => 'Hiring pipeline', 'rows' => array_map(fn (ApplicationStatus $s) => [
            'label' => $s->label(), 'count' => JobApplication::where('status', $s)->count(), 'href' => route('admin.applications.index', ['status' => $s->value]), 'tone' => $s->tone(),
        ], ApplicationStatus::pipeline())];
    }

    /** @return array{title: string, note: string, bars: list<array{label: string, value: int, hint: string}>} */
    private function ticketChart(): array
    {
        return $this->weekly('Service tickets logged by week', 'last 8 weeks', ServiceTicket::where('created_at', '>=', today()->startOfWeek()->subWeeks(7))->pluck('created_at')->all(), past: true);
    }

    /** @return array{title: string, note: string, bars: list<array{label: string, value: int, hint: string}>} */
    private function surveyChart(): array
    {
        return $this->weekly('Surveys requested by week', 'next 8 weeks, open requests', QuoteRequest::open()->whereBetween('survey_on', [today()->startOfWeek(), today()->startOfWeek()->addWeeks(8)])->pluck('survey_on')->all(), past: false);
    }

    /** @return array{title: string, note: string, bars: list<array{label: string, value: int, hint: string}>} */
    private function applicationChart(): array
    {
        return $this->weekly('Applications by week', 'last 8 weeks', JobApplication::where('created_at', '>=', today()->startOfWeek()->subWeeks(7))->pluck('created_at')->all(), past: true);
    }

    /**
     * @param  list<CarbonInterface>  $dates
     * @return array{title: string, note: string, bars: list<array{label: string, value: int, hint: string}>}
     */
    private function weekly(string $title, string $note, array $dates, bool $past): array
    {
        $start = $past ? today()->startOfWeek()->subWeeks(7) : today()->startOfWeek();
        $bars = [];
        for ($i = 0; $i < 8; $i++) {
            $from = $start->copy()->addWeeks($i);
            $to = $from->copy()->addWeek();
            $current = $past ? $i === 7 : $i === 0;
            $bars[] = ['label' => $current ? 'This wk' : $from->format('j M'), 'value' => count(array_filter($dates, fn ($d) => $d >= $from && $d < $to)), 'hint' => 'Week of '.$from->format('j M')];
        }

        return ['title' => $title, 'note' => $note, 'bars' => $bars];
    }
}
