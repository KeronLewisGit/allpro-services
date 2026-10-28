@php($services = (array) allpro('services'))
@php($sectors = (array) allpro('sectors'))
@php($keys = array_keys($services))
<x-layouts.site title="Services" description="Facilities management, contract cleaning, security, industrial cleaning and maintenance, washroom services, consumables, landscaping and pest control from Allpro Services, across Ireland.">
    <x-page-header title="Services" kicker="From the inside to the outside" code="S-01" photo="business-park"
        lead="Take one service or bring them together under one facilities management contract. Every line below is something Allpro already provides." />

    <nav class="wrap mt-10" aria-label="Services on this page">
        <ul class="tag-row">
            @foreach ($services as $key => $s)
                <li class="flex-none" data-svc="{{ $key }}"><a class="tag" href="#{{ $key }}"><span class="h-3 w-3 flex-none rounded-full bg-[var(--svc)]" aria-hidden="true"></span>{{ $s['title'] }}</a></li>
            @endforeach
        </ul>
    </nav>

    {{-- One colour band per service, as on the old home page, each cutting diagonally into the next. --}}
    <div class="mt-10">
        @foreach ($services as $key => $s)
            @php($next = $keys[$loop->index + 1] ?? null)
            <section id="{{ $key }}" data-svc="{{ $key }}" class="svc-band scroll-mt-24" aria-labelledby="{{ $key }}-title">
                <div class="absolute inset-0 -z-10">
                    <x-photo :key="$s['photo']" alt="" sizes="100vw" class="h-full w-full" />
                    <div class="tint"></div>
                </div>
                <div class="wrap grid gap-10 pb-24 pt-14 md:pb-32 md:pt-20 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-16">
                    <div class="min-w-0">
                        <x-lanyard code="{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) count($services), 2, '0', STR_PAD_LEFT) }}" data-reveal>{{ $s['tagline'] }}</x-lanyard>
                        <h2 id="{{ $key }}-title" class="display-2 mt-5">{{ $s['title'] }}</h2>
                        <p class="measure mt-4 text-lg text-white">{{ $s['summary'] }}</p>
                        <a href="{{ route('quote.create', ['service' => $key]) }}" class="tagline mt-7">{{ $s['tagline'] }}<span class="sr-only">: get a quote for {{ \Illuminate\Support\Str::lower($s['title']) }}</span><x-icon name="arrow-right" class="h-4 w-4" /></a>
                    </div>
                    @if ($s['items'])
                        <ul class="ticked grid content-start gap-3 self-center rounded-lg bg-black/20 p-6 text-white ring-1 ring-white/25 sm:grid-cols-2" data-reveal>
                            @foreach ($s['items'] as $item)<li>{{ $item }}</li>@endforeach
                        </ul>
                    @endif
                </div>
                @unless (in_array($s['photo'], (array) allpro('own_photos'), true))<span class="photo-note" style="bottom: auto; top: 0.75rem">Illustrative photo</span>@endunless
                <div class="cut {{ $loop->even ? 'rev' : '' }}" aria-hidden="true" @if($next) style="--cut-bg: var(--svc-{{ $next }})" @endif></div>
            </section>
        @endforeach
    </div>

    <section class="wrap mt-16" aria-labelledby="sectors-title">
        <x-lanyard code="S-02">Sectors</x-lanyard>
        <h2 id="sectors-title" class="display-3 mt-4">Who we work for</h2>
        <ul class="mt-6 flex flex-wrap gap-2">
            @foreach ($sectors as $label)<li class="chip px-3 py-2 text-[0.9375rem]">{{ $label }}</li>@endforeach
        </ul>
        @if (in_array('security', $keys, true))
            <p class="hint mt-6 measure">Managed security services are delivered by our PSA-licensed contractor, for clients in the private and public sectors. Ask your account manager for the licence details for your contract.</p>
        @endif
    </section>

    <section class="wrap mt-16">
        <div class="relative isolate overflow-hidden rounded-lg bg-deep-900 p-8 text-white on-dark md:flex md:items-center md:justify-between md:gap-8 md:p-10">
            <div><h2 class="display-3">Have a site that needs looking after?</h2><p class="mt-2 text-white">Pick a date for a site survey and the team for your region will confirm it.</p></div>
            <a href="{{ route('quote.create') }}" class="btn btn-light mt-6 flex-none md:mt-0">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>
</x-layouts.site>
