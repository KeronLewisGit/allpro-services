@php
    use App\Enums\TicketCategory;
    use App\Enums\TicketPriority;
    use App\Enums\TicketType;
@endphp
<x-layouts.site :title="'Service desk: '.$account->site_name" :mobile-bar="false">
    <x-page-header :title="$account->site_name" :kicker="'Service desk · '.$account->organisation" :code="$account->region->code()" photo="business-park"
        lead="Log a request, a complaint or a security incident for this site. You get a reference number straight away, and updates appear below."
        card-head="Your site" :card="['Region' => $account->region->label(), 'Link valid to' => $until->format('j M Y'), '*Address' => $account->address ?: $account->site_name, '*Helpdesk' => format_phone(allpro('phone'))]" />

    <section class="wrap mt-10 grid gap-12 md:mt-14 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="min-w-0">
            @if (session('logged'))
                <div class="panel mb-8 flex gap-3 border-[color:var(--good)] p-5" role="status">
                    <x-icon name="check-circle" class="mt-0.5 h-6 w-6 flex-none text-[color:var(--good)]" />
                    <div><p class="font-semibold">Logged as <span class="num">{{ session('logged') }}</span>.</p><p class="text-ink-2">The operations team has been told. Quote this reference if you call.</p></div>
                </div>
            @endif
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-10" x-data="sendForm" @submit="submit">
                @csrf
                <x-form-errors />
                <x-honeypot />
                <x-fieldset n="1" title="What is it?">
                    <x-radios name="type" label="Type" :options="collect(TicketType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :required="true" cols="sm:grid-cols-3" />
                    <x-select name="category" label="Area" :required="true" :options="collect(TicketCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
                    <fieldset>
                        <legend class="label">How urgent?</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach (TicketPriority::cases() as $p)
                                <label class="choice"><input type="radio" name="priority" value="{{ $p->value }}" @checked(old('priority', 'normal') === $p->value) required><span><span class="font-semibold">{{ $p->label() }}</span><small>{{ $p->hint() }} · response within {{ duration_short($p->responseHours() * 60) }}</small></span></label>
                            @endforeach
                        </div>
                        <x-error for="priority" />
                    </fieldset>
                    <x-field name="location" label="Where on site?" :optional="true" maxlength="160" hint="For example: Block B, second floor washrooms." />
                    <x-field name="description" label="Describe it" type="textarea" :required="true" maxlength="3000" rows="5" />
                    <div>
                        <label for="f-photos" class="label">Photos <span class="opt">(optional)</span></label>
                        <input id="f-photos" type="file" name="photos[]" multiple accept="image/*,.heic" class="input" aria-describedby="f-photos-hint">
                        <span id="f-photos-hint" class="hint">Up to three, 8 MB each.</span>
                        <x-held-upload form="ticket" field="photos" />
                        <x-error for="photos" /><x-error for="photos.*" />
                    </div>
                </x-fieldset>
                <x-fieldset n="2" title="Who is logging it?">
                    <x-field name="reporter_name" label="Your name" :required="true" maxlength="120" autocomplete="name" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="reporter_email" label="Email" type="email" maxlength="255" autocomplete="email" hint="For updates by email." />
                        <x-field name="reporter_phone" label="Phone" type="tel" maxlength="30" autocomplete="tel" inputmode="tel" hint="Email or phone, at least one." />
                    </div>
                </x-fieldset>
                <div class="border-t border-line pt-6"><x-submit label="Log it" /></div>
            </form>
        </div>
        <aside>
            <h2 class="display-3 text-[1.5rem]">Recent requests</h2>
            @forelse ($tickets as $t)
                <article class="panel mt-4 p-4">
                    <p class="flex flex-wrap items-center justify-between gap-2"><span class="num font-semibold">{{ $t->reference }}</span><x-status :status="$t->status" /></p>
                    <p class="mt-1 text-sm text-ink-3">{{ $t->type->label() }} · {{ $t->category->label() }} · {{ $t->created_at->format('j M, H:i') }}</p>
                    <p class="mt-2 line-clamp-3 text-ink-2">{{ $t->description }}</p>
                    @if ($t->status->isOpen())
                        <p class="mt-2"><x-chip :tone="$t->slaTone()" icon="clock">{{ $t->slaLabel() }}</x-chip></p>
                    @endif
                    @foreach ($t->notes as $n)
                        <p class="mt-3 border-l-2 border-primary pl-3 text-[0.9375rem]"><span class="block text-xs text-ink-3">{{ $n->created_at->format('j M, H:i') }} · Allpro</span>{{ $n->body }}</p>
                    @endforeach
                </article>
            @empty
                <p class="panel mt-4 p-4 text-ink-2">Nothing logged yet for this site.</p>
            @endforelse
            <p class="hint mt-6">This link is private to {{ $account->organisation }}. Please don't share it outside your organisation.</p>
        </aside>
    </section>
</x-layouts.site>
