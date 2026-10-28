@php($services = (array) allpro('services'))
@php($sectors = (array) allpro('sectors'))
<x-layouts.site title="Services" description="Facilities management, contract cleaning, security, industrial cleaning and maintenance, washroom services, consumables, landscaping and pest control from Allpro Services, across Ireland.">
    <x-page-header title="Services" kicker="From the inside to the outside" code="S-01" photo="business-park"
        lead="Take one service or bring them together under one facilities management contract. Every line below is something Allpro already provides." />

    <nav class="wrap mt-10" aria-label="Services on this page">
        <ul class="tag-row">
            @foreach ($services as $key => $s)
                <li class="flex-none"><a class="tag" href="#{{ $key }}"><x-icon :name="$s['icon']" class="h-4 w-4" />{{ $s['title'] }}</a></li>
            @endforeach
        </ul>
    </nav>

    <div class="wrap mt-8 space-y-6">
        @foreach ($services as $key => $s)
            <section id="{{ $key }}" class="sheet scroll-mt-28 grid gap-8 overflow-hidden p-6 md:p-8 lg:grid-cols-[minmax(0,1fr)_24rem]" aria-labelledby="{{ $key }}-title">
                <div class="min-w-0">
                    <div class="flex items-center gap-4"><x-checkpoint :n="$loop->iteration" :light="true" class="text-primary" /><x-icon :name="$s['icon']" class="h-7 w-7 text-primary" /><span class="kicker text-ink-3">{{ $s['tagline'] }}</span></div>
                    <h2 id="{{ $key }}-title" class="display-3 mt-4">{{ $s['title'] }}</h2>
                    <p class="measure mt-3 text-ink-2">{{ $s['summary'] }}</p>
                    @if ($s['items'])
                        <ul class="ticked mt-5 grid gap-2.5 sm:grid-cols-2">
                            @foreach ($s['items'] as $item)<li>{{ $item }}</li>@endforeach
                        </ul>
                    @endif
                    <a href="{{ route('quote.create', ['service' => $key]) }}" class="btn btn-outline btn-sm mt-6">Get a quote for {{ \Illuminate\Support\Str::lower($s['title']) }}<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                </div>
                <figure class="relative -mx-6 -mb-6 md:-mx-8 md:-mb-8 lg:-my-8 lg:-mr-8 lg:ml-0">
                    <x-photo :key="$s['photo']" alt="" sizes="(min-width: 1024px) 24rem, 100vw" class="h-full min-h-56 w-full" />
                    @unless (in_array($s['photo'], (array) allpro('own_photos'), true))<span class="photo-note">Illustrative photo</span>@endunless
                </figure>
            </section>
        @endforeach
    </div>

    <section class="wrap mt-16" aria-labelledby="sectors-title">
        <x-lanyard code="S-02">Sectors</x-lanyard>
        <h2 id="sectors-title" class="display-3 mt-4">Who we work for</h2>
        <ul class="mt-6 flex flex-wrap gap-2">
            @foreach ($sectors as $label)<li class="chip px-3 py-2 text-[0.9375rem]">{{ $label }}</li>@endforeach
        </ul>
        @if (in_array('security', array_keys($services), true))
            <p class="hint mt-6 measure">Managed security services are delivered by our PSA-licensed contractor, for clients in the private and public sectors. Ask your account manager for the licence details for your contract.</p>
        @endif
    </section>

    <section class="wrap mt-16">
        <div class="siteplan relative isolate overflow-hidden rounded-2xl bg-deep-900 p-8 text-white on-dark md:flex md:items-center md:justify-between md:gap-8 md:p-10">
            <div><h2 class="display-3">Have a site that needs looking after?</h2><p class="mt-2 text-white/80">Pick a date for a site survey and the team for your region will confirm it.</p></div>
            <a href="{{ route('quote.create') }}" class="btn btn-hivis mt-6 flex-none md:mt-0">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>
</x-layouts.site>
