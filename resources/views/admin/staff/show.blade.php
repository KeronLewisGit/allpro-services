<x-layouts.admin :title="$staff->full_name">
    <x-slot:actions>
        <a href="{{ route('admin.staff.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Licence register</a>
        <a href="{{ route('admin.staff.edit', $staff) }}" class="a-btn a-btn-primary"><x-icon name="settings" class="h-4 w-4" />Edit</a>
    </x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
        <section class="a-card min-w-0 p-5 md:p-6">
            @php($state = $staff->psaState())
            <div class="flex flex-wrap items-center gap-2">
                @include('admin.partials.psa-chip', ['state' => $state])
                @unless ($staff->active)<x-chip tone="muted" icon="x">Former staff</x-chip>@endunless
            </div>
            <dl class="dl-grid mt-5">
                <dt>Employee no.</dt><dd class="num">{{ $staff->employee_no }}</dd>
                <dt>Role</dt><dd>{{ $staff->role_type->label() }}</dd>
                <dt>Region</dt><dd>{{ $staff->region->label() }}</dd>
                <dt>Phone</dt><dd>@if($staff->phone)<a class="text-link underline" href="{{ tel_href($staff->phone) }}">{{ format_phone($staff->phone) }}</a>@else—@endif</dd>
                <dt>Email</dt><dd>{{ $staff->email ?? '—' }}</dd>
                <dt>Started</dt><dd>{{ $staff->started_on?->format('j F Y') ?? '—' }}</dd>
                <dt>PSA licence</dt><dd class="num">{{ $staff->psa_licence_number ?? 'None recorded' }}</dd>
                <dt>Licence expires</dt><dd>{{ $staff->psa_licence_expires_on?->format('j F Y') ?? '—' }}@if($staff->daysLeft() !== null && $staff->daysLeft() >= 0) <span class="text-ink-3">({{ $staff->daysLeft() }} days)</span>@endif</dd>
                <dt>Last reminder</dt><dd>{{ $staff->psa_reminded_at?->format('j M Y, H:i') ?? 'None sent' }}</dd>
                <dt>Garda vetting</dt><dd>{{ $staff->vetting_status->label() }}{{ $staff->vetting_cleared_on ? ', '.$staff->vetting_cleared_on->format('j M Y') : '' }}</dd>
                <dt>Right to work checked</dt><dd>{{ $staff->rtw_checked_on?->format('j M Y') ?? 'Not recorded' }}</dd>
                @if ($staff->application)<dt>Hired from</dt><dd><a class="text-link underline" href="{{ route('admin.applications.show', $staff->application) }}">{{ $staff->application->reference }}</a></dd>@endif
            </dl>
            @if ($state === 'expired')
                <div class="mt-6 rounded-lg bg-surface-2 p-4 text-sm"><p class="font-semibold">This licence has expired.</p><p class="mt-1 text-ink-2">Take {{ $staff->first_name }} off licensed security work until the renewed licence arrives, then update the expiry date here.</p></div>
            @endif
        </section>
        @include('admin.partials.timeline', ['notes' => $staff->notes, 'action' => route('admin.staff.note', $staff)])
    </div>
</x-layouts.admin>
