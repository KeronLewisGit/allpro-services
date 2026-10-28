@php
    use App\Enums\QuoteStatus;
    use App\Enums\Region;
    $extra = array_diff_key($filters, ['status' => 1]);
@endphp
<x-layouts.admin title="Quote requests">
    <x-slot:actions>
        <a href="{{ route('admin.quotes.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('admin.quotes.index', ['booking' => 1]) }}" class="a-btn a-btn-ghost"><x-icon name="calendar" class="h-4 w-4" />Surveys to book</a>
    </x-slot:actions>
    @include('admin.partials.status-tabs', ['cases' => QuoteStatus::cases(), 'counts' => $counts, 'current' => $filters['status'], 'route' => 'admin.quotes.index', 'extra' => $extra])
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div><label for="f-region" class="mb-1 block text-sm text-ink-2">Region</label>
            <select id="f-region" name="region" class="a-input"><option value="">All</option>@foreach (Region::cases() as $c)<option value="{{ $c->value }}" @selected($filters['region'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <div><label for="f-service" class="mb-1 block text-sm text-ink-2">Service</label>
            <select id="f-service" name="service" class="a-input max-w-[15rem]"><option value="">All</option>@foreach ((array) allpro('services') as $k => $s)<option value="{{ $k }}" @selected($filters['service'] === $k)>{{ $s['title'] }}</option>@endforeach</select></div>
        <div><label for="f-owner" class="mb-1 block text-sm text-ink-2">Owner</label>
            <select id="f-owner" name="owner" class="a-input"><option value="">Anyone</option><option value="none" @selected($filters['owner'] === 'none')>Unassigned</option>@foreach ($owners as $o)<option value="{{ $o->id }}" @selected($filters['owner'] === (string) $o->id)>{{ $o->name }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="booking" value="1" @checked($filters['booking']) class="h-5 w-5 accent-[var(--primary)]">Survey within {{ \App\Models\QuoteRequest::SURVEY_SOON_DAYS }} days, not booked</label>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Organisation, town, contact or reference"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Organisation and site</th><th>Services</th><th>Region</th><th>Owner</th><th>Survey</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($quotes as $q)
                        <tr>
                            <td class="min-w-[14rem]"><a href="{{ route('admin.quotes.show', $q) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $q->organisation }}</a><p class="text-xs text-ink-3"><span class="num">{{ $q->reference }}</span> · {{ $q->site_name }}, {{ $q->town }}</p></td>
                            <td class="min-w-[10rem] text-sm">{{ implode(', ', $q->serviceLabels()) }}</td>
                            <td>{{ $q->region->label() }}</td>
                            <td class="whitespace-nowrap">{{ $q->assignee->name ?? '' }}@unless($q->assignee)<x-chip tone="attention" icon="alert">Unassigned</x-chip>@endunless</td>
                            <td class="whitespace-nowrap">@if ($q->survey_at)<x-chip tone="good" icon="check">Booked {{ $q->survey_at->format('j M, H:i') }}</x-chip>@elseif ($q->status->isOpen())<x-chip :tone="$q->surveyTone()" icon="calendar">Asked {{ $q->survey_on->format('D j M') }} · {{ $q->surveyLabel() }}</x-chip>@else<span class="text-ink-3">{{ $q->survey_on->format('j M') }}</span>@endif</td>
                            <td><x-status :status="$q->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-3">No quote requests match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $quotes->links() }}</div>
</x-layouts.admin>
