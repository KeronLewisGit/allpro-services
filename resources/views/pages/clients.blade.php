@php use App\Enums\TicketPriority; @endphp
<x-layouts.site title="Client services" description="Existing Allpro Services clients: log requests, complaints and security incidents for your site on a private service-desk link, with a reference number and response times.">
    <x-page-header title="Client service desk" kicker="Existing clients" code="CS-01" photo="cctv" position="center 40%"
        lead="Every site on contract gets its own private link. Log a request, a complaint or a security incident at any time, add photos, and follow progress on the same page." />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0">
            <x-lanyard code="CS-02">How it works</x-lanyard>
            <ol class="mt-8 space-y-8">
                <li class="flex gap-5"><x-checkpoint n="1" :light="true" class="text-primary" /><div><h2 class="font-display text-xl font-bold">Your account manager sends the link</h2><p class="mt-1 measure text-ink-2">One link per site, emailed to the contact you choose. It works on any phone or computer without a password, so the site manager, reception or a caretaker can use it. Bookmark it.</p></div></li>
                <li class="flex gap-5"><x-checkpoint n="2" :light="true" class="text-primary" /><div><h2 class="font-display text-xl font-bold">Log it in under a minute</h2><p class="mt-1 measure text-ink-2">Choose request, complaint or security incident, say how urgent it is, and describe it. Add up to three photos. You get a reference number straight away.</p></div></li>
                <li class="flex gap-5"><x-checkpoint n="3" :light="true" class="text-primary" /><div><h2 class="font-display text-xl font-bold">We respond against the clock</h2><p class="mt-1 measure text-ink-2">Each request has a response time and a target fix time set by its urgency. The operations team sees the clock running; you see each update on your link, and by email.</p></div></li>
            </ol>

            {{-- Response clocks: one ring per urgency, counting down as an example of what a client sees. --}}
            <section class="mt-14" aria-labelledby="clocks-title">
                <x-lanyard code="CS-03" data-reveal>Against the clock</x-lanyard>
                <h2 id="clocks-title" class="display-3 mt-4">Every request starts a response clock</h2>
                <p class="mt-2 measure text-ink-2">The ring empties as the response time runs down; staff see the same clock, and the overview lists anything close to running out.</p>
                <div class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-4">
                    @foreach (TicketPriority::cases() as $p)
                        @php($svc = ['urgent' => 'industrial', 'high' => 'washroom', 'normal' => 'cleaning', 'low' => 'pest'][$p->value] ?? 'cleaning')
                        <figure class="text-center" data-svc="{{ $svc }}" x-data="slaClock({{ $p->responseHours() }})" data-reveal style="--reveal-delay: {{ $loop->index * 90 }}ms">
                            <div class="relative mx-auto max-w-[9rem]">
                                <svg viewBox="0 0 100 100" class="sla-ring" aria-hidden="true"><circle class="track" cx="50" cy="50" r="46" /><circle class="arc" cx="50" cy="50" r="46" :style="`--off: ${offset}`" /></svg>
                                <span class="num absolute inset-0 grid place-items-center text-[0.95rem] font-black" x-text="label">{{ sprintf('%02d:00:00', $p->responseHours()) }}</span>
                            </div>
                            <figcaption class="mt-3"><span class="block font-black uppercase tracking-[0.06em]">{{ $p->label() }}</span><span class="block text-sm text-ink-2">Respond within {{ duration_short($p->responseHours() * 60) }}</span></figcaption>
                        </figure>
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-ink-3">Example clocks. On your link, each request shows its own.</p>
            </section>

            <h2 class="display-3 mt-14">Standard response times</h2>
            <p class="mt-2 measure text-ink-2">Your contract can set different times; these are the defaults the service desk starts from.</p>
            <div class="table-box mt-5 rounded-xl border border-line">
                <table class="w-full min-w-[32rem] text-left">
                    <thead class="bg-surface-2 text-sm"><tr><th class="px-4 py-3">Urgency</th><th class="px-4 py-3">For example</th><th class="px-4 py-3 text-right">First response</th><th class="px-4 py-3 text-right">Target fix</th></tr></thead>
                    <tbody>
                        @foreach (TicketPriority::cases() as $p)
                            <tr class="border-t border-line"><td class="px-4 py-3"><x-chip :tone="$p->tone()" icon="clock">{{ $p->label() }}</x-chip></td><td class="px-4 py-3 text-ink-2">{{ $p->hint() }}</td><td class="num px-4 py-3 text-right font-semibold">{{ duration_short($p->responseHours() * 60) }}</td><td class="num px-4 py-3 text-right font-semibold">{{ duration_short($p->resolveHours() * 60) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <aside class="space-y-6">
            <div class="relative isolate overflow-hidden rounded-lg bg-deep-900 p-6 text-white on-dark">
                <p class="font-display text-xl font-bold">Lost your link?</p>
                <p class="mt-2 text-white">Call the helpdesk and we will send a new one to your site contact.</p>
                <a href="{{ tel_href(allpro('phone')) }}" class="btn btn-light mt-5 w-full"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(allpro('phone')) }}</a>
                <a href="mailto:{{ allpro('email') }}" class="mt-3 inline-flex min-h-11 items-center text-white underline underline-offset-4">{{ allpro('email') }}</a>
            </div>
            <div class="panel p-5">
                <p class="font-semibold">Emergency on site?</p>
                <p class="mt-1 text-ink-2">If anyone is in danger, call 112 or 999 first. Then log a security incident or phone the helpdesk.</p>
            </div>
            <div class="panel p-5">
                <p class="font-semibold">Site audits</p>
                <p class="mt-1 text-ink-2">Clients with access to Allpro's client audit system can still sign in there.</p>
                <a href="{{ allpro('client_audit_url') }}" class="btn btn-outline btn-sm mt-4" rel="noopener" target="_blank">Client audit sign-in<x-icon name="external" class="h-4 w-4" /></a>
            </div>
        </aside>
    </section>
</x-layouts.site>
