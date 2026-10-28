<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Area;
use App\Enums\QuoteStatus;
use App\Enums\Region;
use App\Http\Controllers\Controller;
use App\Models\QuoteFile;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Quote and site-survey requests, by region and sales owner. */
class QuoteController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', QuoteRequest::class);
        $filters = $this->filters($request);
        $counts = QuoteRequest::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.quotes.index', [
            'quotes' => QuoteRequest::with('assignee')->filter($filters)
                ->orderByRaw("case when status = 'new' then 0 when status in ('survey_booked', 'surveyed', 'quoted') then 1 else 2 end")
                ->orderBy('survey_on')->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
            'owners' => User::working(Area::Quotes)->orderBy('name')->get(['id', 'name', 'region']),
        ]);
    }

    public function show(QuoteRequest $quote): View
    {
        Gate::authorize('view', $quote);

        return view('admin.quotes.show', [
            'quote' => $quote->load(['files', 'assignee', 'notes.user']),
            'owners' => User::working(Area::Quotes)->orderBy('name')->get(['id', 'name', 'region']),
        ]);
    }

    public function update(Request $request, QuoteRequest $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);
        $data = $request->validate([
            'status' => ['required', Rule::enum(QuoteStatus::class)],
            'assigned_to' => ['nullable', 'integer', Rule::in(User::working(Area::Quotes)->pluck('id')->all())],
            'survey_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'required_if:status,survey_booked'],
            'quoted_value' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'required_if:status,quoted'],
        ], [
            'assigned_to.in' => 'Assign the request to someone in sales.',
            'survey_at.required_if' => 'Enter the date and time the survey is booked for.',
            'quoted_value.required_if' => 'Enter the quoted annual value in euro.',
        ]);
        $user = $this->user($request);

        $assignee = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if ($assignee !== $quote->assigned_to) {
            $quote->update(['assigned_to' => $assignee]);
            $quote->log($assignee ? 'Assigned to '.User::findOrFail($assignee)->name.'.' : 'Unassigned.', 'assign', $user);
        }
        $surveyAt = $data['survey_at'] ?? null;
        if ($surveyAt !== $quote->survey_at?->format('Y-m-d\TH:i')) {
            $quote->update(['survey_at' => $surveyAt]);
            $quote->log($surveyAt ? 'Survey booked for '.$quote->survey_at?->format('l j F, H:i').'.' : 'Survey booking cleared.', 'status', $user);
        }
        $value = isset($data['quoted_value']) ? number_format((float) $data['quoted_value'], 2, '.', '') : null;
        if ($value !== $quote->quoted_value) {
            $quote->update(['quoted_value' => $value]);
            $quote->log($value !== null ? 'Quoted value set to '.eur((float) $value).' a year.' : 'Quoted value cleared.', 'status', $user);
        }
        $status = QuoteStatus::from($data['status']);
        if ($quote->claimStatus($status)) {
            $quote->log('Status changed to '.$status->label().'.', 'status', $user);
        }

        return back()->with('status', "{$quote->reference} saved.");
    }

    public function note(Request $request, QuoteRequest $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $quote->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function file(Request $request, QuoteRequest $quote, QuoteFile $file): StreamedResponse
    {
        Gate::authorize('openFile', $quote);
        abort_unless($file->quote_request_id === $quote->id && Storage::disk('local')->exists($file->path), 404);
        $quote->log("Opened “{$file->original_name}”.", 'file', $this->user($request));

        return Storage::disk('local')->download($file->path, $file->original_name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', QuoteRequest::class);

        return $this->csv('allpro-quote-requests', ['Reference', 'Received', 'Status', 'Region', 'Owner', 'Organisation', 'Sector', 'Services', 'Site', 'Town', 'County', 'Survey requested', 'Survey booked', 'Quoted (EUR/yr)', 'Contact', 'Phone', 'Email'],
            QuoteRequest::with('assignee')->filter($this->filters($request))->lazyByIdDesc(500)->map(fn (QuoteRequest $q) => [
                $q->reference, $q->created_at->format('Y-m-d'), $q->status->label(), $q->region->label(), $q->assignee->name ?? '', $q->organisation,
                (string) allpro('sectors.'.$q->sector), implode('; ', $q->serviceLabels()), $q->site_name, $q->town, $q->county, $q->survey_on->format('Y-m-d'),
                $q->survey_at?->format('Y-m-d H:i'), $q->quoted_value, $q->contact_name, format_phone($q->phone), $q->email,
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $owner = input_string($request->query('owner'));
        $service = input_string($request->query('service'));

        return [
            'status' => QuoteStatus::tryFrom(input_string($request->query('status')))?->value,
            'region' => Region::tryFrom(input_string($request->query('region')))?->value,
            'service' => array_key_exists($service, (array) allpro('services')) ? $service : null,
            'owner' => $owner === 'none' || ctype_digit($owner) ? $owner : null,
            'booking' => $request->boolean('booking') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
