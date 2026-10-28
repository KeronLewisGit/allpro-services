@php
    use App\Enums\Region;
    use App\Enums\RoleType;
    use App\Models\StaffMember;
@endphp
<x-layouts.admin title="Licence register">
    <x-slot:actions>
        <a href="{{ route('admin.staff.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('admin.staff.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add staff member</a>
    </x-slot:actions>
    <p class="measure text-ink-2">Front-line staff, their PSA licences, Garda vetting and right-to-work checks. HR is emailed each morning about licences that expire within {{ StaffMember::EXPIRY_WARNING_DAYS }} days, and again if one lapses.</p>
    <nav class="tag-row mt-4" aria-label="Quick filters">
        <a href="{{ route('admin.staff.index') }}" class="tag" @if(! $filters['due'] && ! $filters['inactive']) aria-current="true" @endif>Active staff</a>
        <a href="{{ route('admin.staff.index', ['due' => 1]) }}" class="tag" @if($filters['due']) aria-current="true" @endif><x-icon name="clock" class="h-4 w-4" />Licences to renew <span class="count">{{ $dueCount }}</span></a>
        <a href="{{ route('admin.staff.index', ['inactive' => 1]) }}" class="tag" @if($filters['inactive']) aria-current="true" @endif>Include former staff</a>
    </nav>
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        @if ($filters['due'])<input type="hidden" name="due" value="1">@endif
        <div><label for="f-role" class="mb-1 block text-sm text-ink-2">Role</label>
            <select id="f-role" name="role" class="a-input"><option value="">All</option>@foreach (RoleType::cases() as $c)<option value="{{ $c->value }}" @selected($filters['role'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <div><label for="f-region" class="mb-1 block text-sm text-ink-2">Region</label>
            <select id="f-region" name="region" class="a-input"><option value="">All</option>@foreach (Region::cases() as $c)<option value="{{ $c->value }}" @selected($filters['region'] === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Name, employee or licence number"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role</th><th>Region</th><th>PSA licence</th><th class="r">Expires</th><th>Vetting</th></tr></thead>
                <tbody>
                    @forelse ($staff as $s)
                        <tr>
                            <td><a href="{{ route('admin.staff.show', $s) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $s->full_name }}</a><p class="num text-xs text-ink-3">{{ $s->employee_no }} @unless ($s->active) · former @endunless</p></td>
                            <td>{{ $s->role_type->label() }}</td>
                            <td>{{ $s->region->label() }}</td>
                            <td class="num">{{ $s->psa_licence_number ?? '—' }}</td>
                            <td class="r whitespace-nowrap">
                                @if ($s->psa_licence_expires_on)
                                    @php($state = $s->psaState())
                                    @if ($state === 'expired')<x-chip tone="critical" icon="alert">Expired {{ $s->psa_licence_expires_on->format('j M') }}</x-chip>
                                    @elseif ($state === 'expiring')<x-chip tone="attention" icon="clock">{{ $s->daysLeft() }} days</x-chip>
                                    @else<span class="num">{{ $s->psa_licence_expires_on->format('j M Y') }}</span>@endif
                                @else<span class="text-ink-3">—</span>@endif
                            </td>
                            <td class="whitespace-nowrap text-sm">{{ $s->vetting_status->label() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-3">Nobody matches.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $staff->links() }}</div>
</x-layouts.admin>
