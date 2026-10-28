@php
    use App\Enums\ApplicationStatus;
    use App\Enums\Region;
    use App\Enums\RoleType;
    use App\Support\Availability;
    $extra = array_diff_key($filters, ['status' => 1]);
@endphp
<x-layouts.admin title="Applications">
    <x-slot:actions>
        <a href="{{ route('admin.applications.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('admin.jobs.index') }}" class="a-btn a-btn-ghost"><x-icon name="briefcase" class="h-4 w-4" />Job postings</a>
    </x-slot:actions>

    {{-- Pipeline board: region by stage, for the role filter in force. --}}
    <section class="a-card mb-5 min-w-0" aria-labelledby="board-title">
        <h2 id="board-title" class="px-5 pt-4 font-semibold">Pipeline by region{{ $filters['role'] ? ': '.RoleType::from($filters['role'])->plural() : '' }}</h2>
        <div class="table-box mt-2">
            <table class="data-table">
                <thead><tr><th>Region</th>@foreach (ApplicationStatus::pipeline() as $s)<th class="r">{{ $s->label() }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach (Region::cases() as $r)
                        <tr>
                            <td class="font-semibold">{{ $r->label() }}</td>
                            @foreach (ApplicationStatus::pipeline() as $s)
                                @php($n = (int) ($board[$r->value][$s->value] ?? 0))
                                <td class="r">@if($n)<a class="num font-semibold text-link hover:underline" href="{{ route('admin.applications.index', array_filter(['region' => $r->value, 'status' => $s->value, 'role' => $filters['role']])) }}">{{ $n }}</a>@else<span class="text-ink-3">·</span>@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @include('admin.partials.status-tabs', ['cases' => ApplicationStatus::cases(), 'counts' => $counts, 'current' => $filters['status'], 'route' => 'admin.applications.index', 'extra' => $extra])
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div><label for="f-role" class="mb-1 block text-sm text-ink-2">Role</label>
            <select id="f-role" name="role" class="a-input"><option value="">All</option>@foreach (RoleType::cases() as $c)<option value="{{ $c->value }}" @selected($filters['role'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <div><label for="f-region" class="mb-1 block text-sm text-ink-2">Region</label>
            <select id="f-region" name="region" class="a-input"><option value="">All</option>@foreach (Region::cases() as $c)<option value="{{ $c->value }}" @selected($filters['region'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <div><label for="f-shift" class="mb-1 block text-sm text-ink-2">Can work</label>
            <select id="f-shift" name="shift" class="a-input"><option value="">Any shift</option>@foreach (Availability::SHIFTS as $k => [$l, $h])<option value="{{ $k }}" @selected($filters['shift'] === $k)>{{ $l }} ({{ $h }})</option>@endforeach</select></div>
        <div><label for="f-job" class="mb-1 block text-sm text-ink-2">Posting</label>
            <select id="f-job" name="job" class="a-input max-w-[16rem]"><option value="">All</option>@foreach ($jobs as $j)<option value="{{ $j->id }}" @selected($filters['job'] === (string) $j->id)>{{ $j->title }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="waiting" value="1" @checked($filters['waiting']) class="h-5 w-5 accent-[var(--primary)]">Waiting over {{ \App\Models\JobApplication::REVIEW_DAYS }} days</label>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Name, phone, PSA number or reference"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Applicant</th><th>Role</th><th>Region</th><th>Shifts</th><th>PSA</th><th>Vetting</th><th>Status</th><th class="r">Received</th></tr></thead>
                <tbody>
                    @forelse ($applications as $a)
                        <tr>
                            <td><a href="{{ route('admin.applications.show', $a) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $a->full_name }}</a><p class="num text-xs text-ink-3">{{ $a->reference }}</p></td>
                            <td class="min-w-[9rem]">{{ $a->role_type->label() }}@if($a->jobPosting)<p class="text-xs text-ink-3">{{ \Illuminate\Support\Str::limit($a->jobPosting->title, 34) }}</p>@endif</td>
                            <td>{{ $a->region->label() }}</td>
                            <td class="min-w-[10rem] text-sm text-ink-2">{{ $a->availabilitySummary() }}</td>
                            <td>@include('admin.partials.psa-chip', ['state' => $a->psaState()])</td>
                            <td class="whitespace-nowrap text-sm">{{ $a->vetting_status->label() }}</td>
                            <td><x-status :status="$a->status" /></td>
                            <td class="r whitespace-nowrap">@if($a->isWaiting())<x-chip tone="attention" icon="clock">{{ $a->created_at->diffForHumans(null, true, true) }}</x-chip>@else{{ $a->created_at->format('j M') }}@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-ink-3">No applications match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $applications->links() }}</div>
</x-layouts.admin>
