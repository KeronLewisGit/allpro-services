@php use App\Models\ClientAccount; @endphp
<x-layouts.admin :title="$account->displayName()">
    <x-slot:actions>
        <a href="{{ route('admin.accounts.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Client sites</a>
        <a href="{{ route('admin.tickets.index', ['account' => $account->id]) }}" class="a-btn a-btn-ghost"><x-icon name="message" class="h-4 w-4" />Tickets</a>
    </x-slot:actions>
    @if ($link)
        <section class="a-card mb-6 border-[color:var(--good)] p-5" aria-labelledby="link-title" x-data="{ copied: false }">
            <h2 id="link-title" class="font-semibold">Service-desk link</h2>
            <p class="mt-1 text-sm text-ink-3">Shown once. Copy it now, or create a new one later.</p>
            <div class="mt-3 flex flex-wrap gap-2"><input readonly value="{{ $link }}" class="a-input min-w-0 flex-1 font-mono text-xs" aria-label="Service-desk link" x-ref="url" @focus="$el.select()">
                <button type="button" class="a-btn a-btn-primary" @click="navigator.clipboard?.writeText($refs.url.value); copied = true"><x-icon name="link" class="h-4 w-4" /><span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span></button></div>
        </section>
    @endif
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Service-desk link</h2>
                <p class="mt-1 text-ink-2">@if ($account->linkIsLive())Live until <strong>{{ $account->link_expires_at?->format('j F Y') }}</strong>.@else No live link. @endif {{ $account->last_opened_at ? 'Last opened '.$account->last_opened_at->diffForHumans().'.' : 'Never opened.' }}</p>
                <form method="POST" action="{{ route('admin.accounts.share', $account) }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div><label for="l-days" class="mb-1 block text-sm text-ink-2">Valid for</label><select id="l-days" name="days" class="a-input">@foreach (ClientAccount::LINK_DAYS as $d)<option value="{{ $d }}" @selected($d === 90)>{{ $d }} days</option>@endforeach</select></div>
                    @if ($account->contact_email)<label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="email" value="1" class="h-4 w-4 accent-[var(--primary)]" checked>Email it to {{ $account->contact_email }}</label>@endif
                    <button class="a-btn a-btn-primary">{{ $account->linkIsLive() ? 'Create a new link' : 'Create link' }}</button>
                </form>
                @if ($account->link_expires_at)
                    <form method="POST" action="{{ route('admin.accounts.revoke', $account) }}" class="mt-3" onsubmit="return confirm('Withdraw every link sent so far for this site?')">
                        @csrf <button class="a-btn a-btn-danger"><x-icon name="x" class="h-4 w-4" />Withdraw all links</button>
                    </form>
                @endif
            </section>
            <section class="a-card min-w-0">
                <h2 class="px-5 pt-4 font-semibold">Tickets</h2>
                <div class="table-box mt-2">
                    <table class="data-table">
                        <thead><tr><th>Reference</th><th>Type</th><th>Status</th><th>SLA</th><th class="r">Logged</th></tr></thead>
                        <tbody>
                            @forelse ($account->tickets->sortByDesc('id') as $t)
                                <tr><td><a class="num font-semibold text-link hover:underline" href="{{ route('admin.tickets.show', $t) }}">{{ $t->reference }}</a></td><td>{{ $t->type->label() }}</td><td><x-status :status="$t->status" /></td><td><x-chip :tone="$t->slaTone()" icon="clock">{{ $t->slaLabel() }}</x-chip></td><td class="r whitespace-nowrap">{{ $t->created_at->format('j M, H:i') }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-ink-3">Nothing logged yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="min-w-0 space-y-6">
            <form method="POST" action="{{ route('admin.accounts.update', $account) }}" class="a-card space-y-4 p-5">
                @csrf @method('PATCH')
                <h2 class="font-semibold">Site details</h2>
                @include('admin.accounts.fields', ['account' => $account])
                <button class="a-btn a-btn-primary">Save</button>
            </form>
            @include('admin.partials.timeline', ['notes' => $account->notes, 'action' => null])
        </div>
    </div>
</x-layouts.admin>
