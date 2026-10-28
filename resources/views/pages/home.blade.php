@php
    $services = (array) allpro('services');
    $regions = (array) allpro('regions');
@endphp
<x-layouts.site>
    {{-- Hero: the home page's own version of the shared band, with the company's facts on the site card. --}}
    <section class="siteplan relative isolate overflow-hidden bg-deep-900 text-white on-dark">
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo key="scrubber" alt="" :eager="true" sizes="100vw" class="h-full w-full" position="center 55%" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
        <div class="wrap grid gap-10 pb-12 pt-12 md:pb-20 md:pt-24 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
            <div class="min-w-0">
                <x-lanyard code="APS">Established {{ allpro('founded') }}</x-lanyard>
                <h1 class="display-1 mt-6 max-w-[16ch]">Clean, secure, well-run buildings across Ireland.</h1>
                <p class="lead mt-6 max-w-[58ch] text-white/85">Allpro Services looks after the day-to-day running of offices, schools, shops, medical sites and public buildings: contract cleaning, security, washrooms, consumables, grounds and pest control, from offices in Galway, Dublin, Limerick and Cork.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('quote.create') }}" class="btn btn-hivis">Request a quote or site survey<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('clients') }}" class="btn btn-on-dark"><x-icon name="message" class="h-4 w-4" />Existing client? Log a request</a>
                </div>
            </div>
            <x-sitecard class="hidden lg:grid" head="Allpro Services" ref="EST. {{ allpro('founded') }}" :fields="['Head office' => 'Oranmore', 'Regions' => '4 offices', '*Helpdesk' => format_phone(allpro('phone')), '*Email' => allpro('email')]" />
        </div>
        <span class="photo-note" style="bottom: 2.25rem">Illustrative photo</span>
        <div class="hivis" aria-hidden="true"></div>
    </section>

    {{-- Three ways in --}}
    <section class="wrap mt-14 md:mt-20" aria-labelledby="ways-title">
        <h2 id="ways-title" class="sr-only">How can we help?</h2>
        <div class="grid gap-5 md:grid-cols-3">
            <a href="{{ route('quote.create') }}" class="sheet group flex flex-col gap-3 p-6 hover:border-primary" data-reveal>
                <span class="flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-xl bg-primary-soft text-primary"><x-icon name="building" class="h-6 w-6" /></span><span class="kicker text-ink-3">New sites</span></span>
                <span class="font-display text-2xl font-bold leading-tight">Get a quote</span>
                <span class="text-ink-2">Tell us about the building and pick a date for a site survey. Your regional team takes it from there.</span>
                <span class="mt-auto inline-flex items-center gap-2 pt-2 font-semibold text-link">Request a survey<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
            </a>
            <a href="{{ route('clients') }}" class="sheet group flex flex-col gap-3 p-6 hover:border-primary" data-reveal style="--reveal-delay: 80ms">
                <span class="flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-xl bg-primary-soft text-primary"><x-icon name="message" class="h-6 w-6" /></span><span class="kicker text-ink-3">Clients</span></span>
                <span class="font-display text-2xl font-bold leading-tight">Log a request or complaint</span>
                <span class="text-ink-2">Each client site gets a private service-desk link with a reference number and a response time for every request.</span>
                <span class="mt-auto inline-flex items-center gap-2 pt-2 font-semibold text-link">How it works<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
            </a>
            <a href="{{ route('careers.index') }}" class="sheet group flex flex-col gap-3 p-6 hover:border-primary" data-reveal style="--reveal-delay: 160ms">
                <span class="flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-xl bg-primary-soft text-primary"><x-icon name="badge" class="h-6 w-6" /></span><span class="kicker text-ink-3">Jobs</span></span>
                <span class="font-display text-2xl font-bold leading-tight">Work with us</span>
                <span class="text-ink-2">{{ $openJobs ? $openJobs.' '.\Illuminate\Support\Str::plural('opening', $openJobs).' for' : 'We hire' }} cleaning operatives, security officers and supervisors in all four regions. No CV needed.</span>
                <span class="mt-auto inline-flex items-center gap-2 pt-2 font-semibold text-link">See openings<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
            </a>
        </div>
    </section>

    {{-- Services --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="services-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-lanyard code="01">From the inside to the outside</x-lanyard>
                <h2 id="services-title" class="display-2 mt-4 max-w-[22ch]">One contract for everything a building needs.</h2>
            </div>
            <a href="{{ route('services') }}" class="btn btn-outline">All services<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($services as $key => $s)
                <a href="{{ route('services') }}#{{ $key }}" class="panel group flex flex-col gap-3 p-6 transition-colors hover:border-primary hover:bg-surface-2" data-reveal>
                    <span class="flex items-center justify-between"><x-icon :name="$s['icon']" class="h-7 w-7 text-primary" /><x-checkpoint :n="$loop->iteration" :light="true" class="text-ink-3" /></span>
                    <span class="font-display text-[1.3rem] font-bold leading-snug">{{ $s['title'] }}</span>
                    <span class="text-[0.975rem] text-ink-2">{{ \Illuminate\Support\Str::of($s['summary'])->before('. ')->finish('.') }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Mission, in the company's own words, beside the company's own photo --}}
    <section class="wrap mt-20 grid items-center gap-10 md:mt-28 lg:grid-cols-[1fr_1.1fr]">
        <figure class="relative">
            <x-photo key="wet-floor" alt="An Allpro cleaner's wet-floor sign with the APS logo on a freshly mopped floor" sizes="(min-width: 1024px) 40vw, 100vw" class="aspect-[4/3] rounded-2xl" />
            <figcaption class="photo-caption">Allpro Services photograph.</figcaption>
        </figure>
        <div>
            <x-lanyard code="02">In our own words</x-lanyard>
            <blockquote class="mt-6">
                <p class="font-display text-[clamp(1.4rem,1.1rem+1vw,2rem)] font-semibold leading-snug">“{{ allpro('about.mission') }}”</p>
                <footer class="mt-4 text-ink-3">Allpro Services mission statement</footer>
            </blockquote>
            <a href="{{ route('about') }}" class="btn btn-outline mt-8">About Allpro<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>

    {{-- Regions --}}
    <section class="siteplan mt-20 bg-deep-900 py-16 text-white on-dark md:mt-28 md:py-24" aria-labelledby="regions-title">
        <div class="wrap">
            <x-lanyard code="03">Four regions</x-lanyard>
            <h2 id="regions-title" class="display-2 mt-4 max-w-[24ch]">Local teams in Galway, Dublin, Limerick and Cork.</h2>
            <p class="lead mt-4 text-white/80">Quote requests go straight to the sales team for the county the site is in, and staff are recruited close to the sites they work on.</p>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($regions as $key => $r)
                    <article class="overflow-hidden rounded-2xl bg-deep-800 ring-1 ring-white/10" data-reveal>
                        <div class="relative">
                            <x-photo :key="$r['photo']" alt="" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" class="aspect-[4/3]" />
                            <span class="photo-note">Illustrative photo</span>
                        </div>
                        <div class="p-5">
                            <p class="flex items-center justify-between gap-3"><span class="font-display text-2xl font-bold">{{ $r['label'] }}</span><span class="rounded-md bg-hivis px-2 py-1 font-display text-sm font-bold text-deep-950">{{ $r['code'] }}</span></p>
                            <p class="mt-1 text-white/75">{{ $r['office'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
            <a href="{{ route('contact') }}" class="btn btn-on-dark mt-10">Contact details<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>

    {{-- How a quote works --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="how-title">
        <x-lanyard code="04">New contracts</x-lanyard>
        <h2 id="how-title" class="display-2 mt-4 max-w-[22ch]">From first call to first shift.</h2>
        <ol class="mt-10 grid gap-6 lg:grid-cols-3">
            <li class="sheet p-6">
                <x-checkpoint n="1" :light="true" class="text-primary" />
                <h3 class="mt-4 font-display text-xl font-bold">Tell us about the site</h3>
                <p class="mt-2 text-ink-2">Which services, the address, the size and the cover you need. Attach floor plans or a tender specification if you have them.</p>
            </li>
            <li class="sheet p-6">
                <x-checkpoint n="2" :light="true" class="text-primary" />
                <h3 class="mt-4 font-display text-xl font-bold">Pick a survey date</h3>
                <p class="mt-2 text-ink-2">Choose a weekday and a time of day. The sales owner for your region confirms the visit and walks the site with you.</p>
            </li>
            <li class="sheet p-6">
                <x-checkpoint n="3" :light="true" class="text-primary" />
                <h3 class="mt-4 font-display text-xl font-bold">Get a costed proposal</h3>
                <p class="mt-2 text-ink-2">A quote built around the survey. Once on contract, your site gets its own service-desk link for requests and complaints.</p>
            </li>
        </ol>
        <a href="{{ route('quote.create') }}" class="btn btn-primary mt-8">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
    </section>

    {{-- Contact strip --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="visit-title">
        <div class="grid gap-8 border-t border-line pt-10 md:grid-cols-3">
            <div><h2 id="visit-title" class="display-3">Talk to the helpdesk</h2><p class="mt-2 text-ink-2">Questions about a service, a site or a job: call or email the head office in Oranmore.</p></div>
            <div>
                <p class="kicker text-ink-3">Telephone</p>
                <a href="{{ tel_href(allpro('phone')) }}" class="mt-1 inline-flex min-h-11 items-center font-display text-2xl font-bold text-link">{{ format_phone(allpro('phone')) }}</a>
                <p class="kicker mt-3 text-ink-3">Email</p>
                <a href="mailto:{{ allpro('email') }}" class="mt-1 inline-flex min-h-11 items-center text-lg font-semibold text-link">{{ allpro('email') }}</a>
            </div>
            <div>
                <p class="kicker text-ink-3">Head office</p>
                <p class="mt-1 text-lg">{{ allpro('address.street') }}<br>{{ allpro('address.town') }}, {{ allpro('address.county') }}</p>
                <a href="{{ allpro('map_url') }}" target="_blank" rel="noopener" class="mt-1 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">Get directions<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens Google Maps)</span></a>
            </div>
        </div>
    </section>
</x-layouts.site>
