@php
    use App\Enums\Region;
    use App\Enums\TicketPriority;
    use App\Enums\TicketStatus;
    use App\Enums\TicketType;
    $extra = array_diff_key($filters, ['status' => 1]);
@endphp
<x-layouts.admin title="Service desk">
    <x-slot:actions>
        <a href="{{ route('admin.tickets.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('admin.accounts.index') }}" class="a-btn a-btn-ghost"><x-icon name="building" class="h-4 w-4" />Client sites</a>
    </x-slot:actions>
    @include('admin.partials.status-tabs', ['cases' => TicketStatus::cases(), 'counts' => $counts, 'current' => $filters['status'], 'route' => 'admin.tickets.index', 'extra' => $extra])
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @if ($filters['account'])<input type="hidden" name="account" value="{{ $filters['account'] }}">@endif
        <div><label for="f-type" class="mb-1 block text-sm text-ink-2">Type</label>
            <select id="f-type" name="type" class="a-input"><option value="">All</option>@foreach (TicketType::cases() as $c)<option value="{{ $c->value }}" @selected($filters['type'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <div><label for="f-pri" class="mb-1 block text-sm text-ink-2">Priority</label>
            <select id="f-pri" name="priority" class="a-input"><option value="">All</option>@foreach (TicketPriority::cases() as $c)<option value="{{ $c->value }}" @selected($filters['priority'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <div><label for="f-region" class="mb-1 block text-sm text-ink-2">Region</label>
            <select id="f-region" name="region" class="a-input"><option value="">All</option>@foreach (Region::cases() as $c)<option value="{{ $c->value }}" @selected($filters['region'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="risk" value="1" @checked($filters['risk']) class="h-5 w-5 accent-[var(--primary)]">At SLA risk</label>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Client, site, reporter or reference"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Ticket</th><th>Client and site</th><th>Priority</th><th>Status</th><th>SLA</th><th>Owner</th></tr></thead>
                <tbody>
                    @forelse ($tickets as $t)
                        <tr>
                            <td class="min-w-[12rem]"><a href="{{ route('admin.tickets.show', $t) }}" class="num font-semibold text-link underline-offset-2 hover:underline">{{ $t->reference }}</a><p class="text-xs text-ink-3">{{ $t->type->label() }} · {{ $t->category->label() }} · {{ $t->created_at->format('j M, H:i') }}</p></td>
                            <td class="min-w-[12rem]">{{ $t->account->organisation }}<p class="text-xs text-ink-3">{{ $t->account->site_name }} · {{ $t->account->region->label() }}</p></td>
                            <td><x-chip :tone="$t->priority->tone()" icon="flag">{{ $t->priority->label() }}</x-chip></td>
                            <td><x-status :status="$t->status" /></td>
                            <td class="whitespace-nowrap"><x-chip :tone="$t->slaTone()" icon="clock" class="num">{{ $t->slaLabel() }}</x-chip></td>
                            <td class="whitespace-nowrap text-sm">{{ $t->assignee->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-3">No tickets match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
</x-layouts.admin>
