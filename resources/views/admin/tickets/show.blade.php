@php use App\Enums\TicketStatus; $t = $ticket; @endphp
<x-layouts.admin :title="$t->reference.' · '.$t->account->displayName()">
    <x-slot:actions>
        <a href="{{ route('admin.tickets.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Service desk</a>
        <a href="{{ route('admin.accounts.show', $t->account) }}" class="a-btn a-btn-ghost"><x-icon name="building" class="h-4 w-4" />Client site</a>
        @if ($t->reporter_phone)<a href="{{ tel_href($t->reporter_phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call</a>@endif
    </x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5 md:p-6">
                <div class="flex flex-wrap items-center gap-2"><x-status :status="$t->status" /><x-chip :tone="$t->priority->tone()" icon="flag">{{ $t->priority->label() }}</x-chip><span class="chip">{{ $t->type->label() }}</span></div>
                <p class="mt-4 measure whitespace-pre-line text-[1.0625rem]">{{ $t->description }}</p>
                <dl class="dl-grid mt-5">
                    <dt>Area</dt><dd>{{ $t->category->label() }}</dd>
                    <dt>Where</dt><dd>{{ $t->location ?: 'Not given' }}</dd>
                    <dt>Logged by</dt><dd>{{ $t->reporter_name }}</dd>
                    <dt>Contact</dt><dd>{{ collect([$t->reporter_email, $t->reporter_phone ? format_phone($t->reporter_phone) : null])->filter()->implode(' · ') }}</dd>
                    <dt>Logged</dt><dd>{{ $t->created_at->format('j M Y, H:i') }}</dd>
                </dl>
                @if ($t->photos->isNotEmpty())
                    <h3 class="mt-6 font-semibold">Photos</h3>
                    @foreach ($t->photos as $p)<a href="{{ route('admin.tickets.photo', [$t, $p]) }}" class="mt-2 flex min-h-11 items-center gap-2 text-link hover:underline"><x-icon name="image" class="h-4 w-4" />{{ $p->original_name }} <span class="text-sm text-ink-3">{{ file_size($p->size) }}</span></a>@endforeach
                @endif
            </section>
            <section class="a-card p-5" aria-labelledby="sla-title">
                <h2 id="sla-title" class="font-semibold">Service level</h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    @foreach (['response' => ['First response', $t->response_due_at, $t->responded_at], 'resolve' => ['Resolution', $t->resolve_due_at, $t->resolved_at]] as $k => [$label, $due, $done])
                        @php($state = $t->clock($k))
                        <div class="rounded-lg border border-line p-4">
                            <p class="text-sm text-ink-3">{{ $label }}</p>
                            <p class="mt-1 font-semibold">Due {{ $due->format('j M, H:i') }}</p>
                            <p class="mt-2"><x-chip :tone="match ($state) { 'met' => 'good', 'breached', 'overdue' => 'critical', 'due-soon' => 'attention', default => 'neutral' }" icon="clock">{{ match ($state) { 'met' => 'Met '.$done?->format('j M, H:i'), 'breached' => 'Late, '.$done?->format('j M, H:i'), 'overdue' => duration_short((int) now()->diffInMinutes($due)).' overdue', 'due-soon' => 'Due in '.duration_short((int) now()->diffInMinutes($due)), default => 'On track' } }}</x-chip></p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Update</h2>
                <form method="POST" action="{{ route('admin.tickets.update', $t) }}" class="mt-3 space-y-4">
                    @csrf @method('PATCH')
                    <div><label for="t-owner" class="mb-1 block text-sm text-ink-2">Owner</label>
                        <select id="t-owner" name="assigned_to" class="a-input w-full"><option value="">Unassigned</option>@foreach ($people as $p)<option value="{{ $p->id }}" @selected($t->assigned_to === $p->id)>{{ $p->name }}</option>@endforeach</select></div>
                    <div><label for="t-status" class="mb-1 block text-sm text-ink-2">Status</label>
                        <select id="t-status" name="status" class="a-input w-full">@foreach (TicketStatus::cases() as $s)<option value="{{ $s->value }}" @selected($t->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                    <div><label for="t-msg" class="mb-1 block text-sm text-ink-2">Update for the client <span class="text-ink-3">(optional)</span></label>
                        <textarea id="t-msg" name="client_update" class="a-input" maxlength="2000" placeholder="Shown on the client's link{{ $t->reporter_email ? ' and emailed to '.$t->reporter_email : '' }}"></textarea></div>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
            </section>
            @include('admin.partials.timeline', ['notes' => $t->notes, 'action' => route('admin.tickets.note', $t)])
        </div>
    </div>
</x-layouts.admin>
