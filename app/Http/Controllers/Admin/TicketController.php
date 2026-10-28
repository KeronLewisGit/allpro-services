<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Area;
use App\Enums\Region;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Http\Controllers\Controller;
use App\Models\ServiceTicket;
use App\Models\TicketPhoto;
use App\Models\User;
use App\Notifications\TicketUpdated;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The client service desk: requests and complaints with SLA clocks. */
class TicketController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ServiceTicket::class);
        $filters = $this->filters($request);
        $counts = ServiceTicket::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.tickets.index', [
            'tickets' => ServiceTicket::with(['account', 'assignee'])->filter($filters)
                ->orderByRaw("case when status in ('new', 'acknowledged', 'in_progress') then 0 else 1 end")
                ->orderByRaw('case when responded_at is null then response_due_at else resolve_due_at end')
                ->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
        ]);
    }

    public function show(ServiceTicket $ticket): View
    {
        Gate::authorize('view', $ticket);

        return view('admin.tickets.show', [
            'ticket' => $ticket->load(['account', 'photos', 'assignee', 'notes.user']),
            'people' => User::working(Area::ServiceDesk)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);
        $data = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'assigned_to' => ['nullable', 'integer', Rule::in(User::working(Area::ServiceDesk)->pluck('id')->all())],
            'client_update' => ['nullable', 'string', 'max:2000'],
        ], ['assigned_to.in' => 'Assign the ticket to someone in operations.']);
        $user = $this->user($request);

        $assignee = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if ($assignee !== $ticket->assigned_to) {
            $ticket->update(['assigned_to' => $assignee]);
            $ticket->log($assignee ? 'Assigned to '.User::findOrFail($assignee)->name.'.' : 'Unassigned.', 'assign', $user);
        }
        $status = TicketStatus::from($data['status']);
        $changed = $ticket->claimStatus($status);
        if ($changed) {
            $ticket->log('Status changed to '.$status->label().'.', 'status', $user);
        }
        // An update the client sees on their link, and is emailed when they gave an address.
        if (! empty($data['client_update'])) {
            $ticket->log($data['client_update'], 'client', $user);
            Notify::sender($ticket->reporter_email, new TicketUpdated($ticket, $data['client_update']));
        }

        return back()->with('status', "{$ticket->reference} saved.");
    }

    public function note(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $ticket->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function photo(Request $request, ServiceTicket $ticket, TicketPhoto $photo): StreamedResponse
    {
        Gate::authorize('openPhoto', $ticket);
        abort_unless($photo->service_ticket_id === $ticket->id && Storage::disk('local')->exists($photo->path), 404);
        $ticket->log("Opened photo “{$photo->original_name}”.", 'file', $this->user($request));

        return Storage::disk('local')->download($photo->path, $photo->original_name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', ServiceTicket::class);

        return $this->csv('allpro-service-desk', ['Reference', 'Logged', 'Client', 'Site', 'Region', 'Type', 'Category', 'Priority', 'Status', 'Response due', 'Responded', 'Response SLA', 'Resolve due', 'Resolved', 'Resolution SLA'],
            ServiceTicket::with('account')->filter($this->filters($request))->lazyByIdDesc(500)->map(fn (ServiceTicket $t) => [
                $t->reference, $t->created_at->format('Y-m-d H:i'), $t->account->organisation, $t->account->site_name, $t->account->region->label(), $t->type->label(),
                $t->category->label(), $t->priority->label(), $t->status->label(), $t->response_due_at->format('Y-m-d H:i'), $t->responded_at?->format('Y-m-d H:i'),
                $t->clock('response'), $t->resolve_due_at->format('Y-m-d H:i'), $t->resolved_at?->format('Y-m-d H:i'), $t->clock('resolve'),
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $account = input_string($request->query('account'));

        return [
            'status' => TicketStatus::tryFrom(input_string($request->query('status')))?->value,
            'priority' => TicketPriority::tryFrom(input_string($request->query('priority')))?->value,
            'type' => TicketType::tryFrom(input_string($request->query('type')))?->value,
            'region' => Region::tryFrom(input_string($request->query('region')))?->value,
            'account' => ctype_digit($account) ? $account : null,
            'risk' => $request->boolean('risk') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
