@php
    $services = (array) allpro('services');
    $regions = (array) allpro('regions');
    $headline = ['Facility', 'management,', 'from', 'the', 'inside', 'to', 'the', 'outside.'];
    $firstKey = array_key_first($services);
@endphp
<x-layouts.site>
    {{--
        Hero: the home page's own version of the shared band. Like the old allpro.ie slider, a
        purple-washed photograph under "Facility management"; the headline rises word by word,
        a large APS pebble draws itself, and three sourced figures count up.
        Every word and number is in the HTML, so nothing depends on JavaScript.
    --}}
    <section class="page-header tinted relative isolate overflow-hidden bg-deep-900 text-white on-dark" data-svc="facilities">
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo key="scrubber" alt="" :eager="true" sizes="100vw" class="h-full w-full" position="center 55%" />
            <div class="hero-scrim soft absolute inset-0"></div>
        </div>
        <svg viewBox="0 0 100 62" class="mark-draw pointer-events-none absolute -right-16 top-10 hidden w-[34rem] text-white/[0.14] lg:block" aria-hidden="true">
            <path class="pebble" d="M4.5 42.5C1 35 6 28.5 15 25.2L78.5 2.6C89.5-1.3 96.8 3.4 97.6 14.5l1.2 22.6c.6 12.5-6.4 21.4-19.2 22.3L22.5 61.5C12.8 62.2 7.7 49.2 4.5 42.5Z" fill="currentColor" />
        </svg>
        <div class="wrap grid gap-10 pb-24 pt-12 md:pb-32 md:pt-24 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
            <div class="min-w-0">
                <x-lanyard code="APS" class="hero-fade" style="--d: 100ms">Established {{ allpro('founded') }}</x-lanyard>
                <h1 class="display-1 hero-words mt-6 max-w-[15ch]">
                    @foreach ($headline as $w)<span class="w" style="--i: {{ $loop->index }}">{{ $w }}</span>{{ $loop->last ? '' : ' ' }}@endforeach
                </h1>
                <p class="lead hero-fade mt-6 max-w-[56ch] text-white" style="--d: 850ms">We look after the day-to-day management of your facility, so you can concentrate on your core business: contract cleaning, security, washrooms, consumables, grounds and pest control, from offices in Galway, Dublin, Limerick and Cork.</p>
                <p class="hero-fade mt-3 font-bold text-white" style="--d: 950ms">Tried and trusted by corporates and state bodies alike.</p>
                <div class="hero-fade mt-8 flex flex-wrap gap-3" style="--d: 1050ms">
                    <a href="{{ route('quote.create') }}" class="btn btn-light">Request a quote or site survey<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('clients') }}" class="btn btn-on-dark"><x-icon name="message" class="h-4 w-4" />Existing client? Log a request</a>
                </div>
            </div>
            <x-sitecard class="hero-fade hidden lg:grid" style="--d: 1200ms" head="Allpro Services" ref="EST. {{ allpro('founded') }}" :fields="['Head office' => 'Oranmore', 'Regions' => '4 offices', '*Helpdesk' => format_phone(allpro('phone')), '*Email' => allpro('email')]" />
        </div>
        <span class="photo-note" style="bottom: auto; top: 0.75rem">Illustrative photo</span>
        <div class="cut" aria-hidden="true"></div>
    </section>

    {{-- Figures from allpro.ie: founding year, offices, service lines. They count up once, when seen. --}}
    <section class="wrap relative z-[2] -mt-6 md:-mt-10" aria-label="Allpro at a glance">
        <dl class="grid grid-cols-3 overflow-hidden rounded-lg bg-surface shadow-md ring-1 ring-line">
            <div class="flex flex-col-reverse border-r border-line p-4 text-center md:p-7">
                <dt class="kicker mt-1 text-ink-3">Established</dt>
                <dd class="display-2 num text-link" data-count="{{ allpro('founded') }}" data-from="1990">{{ allpro('founded') }}</dd>
            </div>
            <div class="flex flex-col-reverse border-r border-line p-4 text-center md:p-7">
                <dt class="kicker mt-1 text-ink-3">Regional offices</dt>
                <dd class="display-2 num text-link" data-count="{{ count($regions) }}">{{ count($regions) }}</dd>
            </div>
            <div class="flex flex-col-reverse p-4 text-center md:p-7">
                <dt class="kicker mt-1 text-ink-3">Service lines</dt>
                <dd class="display-2 num text-link" data-count="{{ count($services) }}">{{ count($services) }}</dd>
            </div>
        </dl>
        <p class="mt-2 text-right text-xs text-ink-3">Source: allpro.ie</p>
    </section>

    {{-- The old site's own taglines, one per service, as a slow marquee (pauses on hover or focus). --}}
    <section class="mt-12" aria-labelledby="taglines-title">
        <h2 id="taglines-title" class="sr-only">Our services in a line each</h2>
        <div class="marquee">
            <ul class="marquee-track">
                @foreach ([false, true] as $copy)
                    @foreach ($services as $key => $s)
                        <li data-svc="{{ $key }}" @if($copy) aria-hidden="true" @endif><a href="{{ route('services') }}#{{ $key }}" class="tagline whitespace-nowrap" style="--tag: var(--svc)" @if($copy) tabindex="-1" @endif><x-icon :name="$s['icon']" class="h-4 w-4" />{{ $s['tagline'] }}<span class="sr-only">: {{ $s['title'] }}</span></a></li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Three ways in --}}
    <section class="wrap mt-14 md:mt-20" aria-labelledby="ways-title">
        <h2 id="ways-title" class="sr-only">How can we help?</h2>
        <div class="grid gap-5 md:grid-cols-3">
            @foreach ([
                ['route' => 'quote.create', 'icon' => 'building', 'kicker' => 'New sites', 'title' => 'Get a quote', 'text' => 'Tell us about the building and pick a date for a site survey. Your regional team takes it from there.', 'cta' => 'Request a survey'],
                ['route' => 'clients', 'icon' => 'message', 'kicker' => 'Clients', 'title' => 'Log a request or complaint', 'text' => 'Each client site gets a private service-desk link with a reference number and a response time for every request.', 'cta' => 'How it works'],
                ['route' => 'careers.index', 'icon' => 'badge', 'kicker' => 'Jobs', 'title' => 'Work with us', 'text' => ($openJobs ? $openJobs.' '.\Illuminate\Support\Str::plural('opening', $openJobs).' for' : 'We hire').' cleaning operatives, security officers and supervisors in all four regions. No CV needed.', 'cta' => 'See openings'],
            ] as $card)
                <a href="{{ route($card['route']) }}" class="sheet tilt group flex flex-col gap-3 overflow-hidden p-6 hover:border-primary" data-reveal style="--reveal-delay: {{ $loop->index * 80 }}ms">
                    <span class="absolute inset-x-0 top-0 h-1 bg-primary" aria-hidden="true"></span>
                    <span class="flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-lg bg-primary-soft text-link"><x-icon :name="$card['icon']" class="h-6 w-6" /></span><span class="kicker text-ink-3">{{ $card['kicker'] }}</span></span>
                    <span class="text-2xl font-black uppercase leading-tight tracking-[0.01em]">{{ $card['title'] }}</span>
                    <span class="text-ink-2">{{ $card['text'] }}</span>
                    <span class="mt-auto inline-flex items-center gap-2 pt-2 font-bold text-link">{{ $card['cta'] }}<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </div>
    </section>

    {{--
        Services switcher: the old home page's colour band per service, in one band. Choosing a
        service slides its panel in and washes the band in that service's colour. Without
        JavaScript every panel is shown, one after another.
    --}}
    <section class="svc-band svc-switch mt-20 md:mt-28" data-svc="{{ $firstKey }}" x-data="svcSwitch(@js(array_keys($services)))" :data-svc="active" aria-labelledby="services-title">
        <div class="cut top" aria-hidden="true"></div>
        <div class="wrap pb-24 pt-24 md:pb-32 md:pt-32">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <x-lanyard code="01" data-reveal>From the inside to the outside</x-lanyard>
                    <h2 id="services-title" class="display-2 mt-4 max-w-[20ch]">One contract for everything a building needs.</h2>
                </div>
                <a href="{{ route('services') }}" class="btn btn-on-dark">All services<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <div class="tag-row mt-8 hidden gap-2 [.js_&]:flex" role="tablist" aria-label="Services" @keydown.right.prevent="step(1)" @keydown.left.prevent="step(-1)" @keydown.home.prevent="select(keys[0])" @keydown.end.prevent="select(keys[keys.length - 1])">
                @foreach ($services as $key => $s)
                    <button type="button" role="tab" id="tab-{{ $key }}" class="svc-tab" data-svc="{{ $key }}" aria-controls="panel-{{ $key }}" :aria-selected="(active === '{{ $key }}').toString()" :tabindex="active === '{{ $key }}' ? 0 : -1" @click="select('{{ $key }}')" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}"><x-icon :name="$s['icon']" class="h-4 w-4" />{{ $s['title'] }}</button>
                @endforeach
            </div>
            @foreach ($services as $key => $s)
                <div role="tabpanel" id="panel-{{ $key }}" aria-labelledby="tab-{{ $key }}" tabindex="0" class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:items-center" :class="active === '{{ $key }}' && 'is-active'">
                    <figure class="relative overflow-hidden rounded-lg ring-1 ring-white/25">
                        <x-photo :key="$s['photo']" alt="" sizes="(min-width: 1024px) 40vw, 100vw" class="aspect-[16/10]" />
                        @unless (in_array($s['photo'], (array) allpro('own_photos'), true))<span class="photo-note">Illustrative photo</span>@endunless
                    </figure>
                    <div class="min-w-0">
                        <p class="kicker text-white">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ $s['tagline'] }}</p>
                        <h3 class="mt-3 text-[clamp(1.6rem,1.2rem+1.4vw,2.4rem)] font-black uppercase leading-tight">{{ $s['title'] }}</h3>
                        <p class="measure mt-3 text-lg text-white">{{ $s['summary'] }}</p>
                        @if ($s['items'])
                            <ul class="ticked mt-5 grid gap-2 text-white sm:grid-cols-2">
                                @foreach (array_slice($s['items'], 0, 6) as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        @endif
                        <a href="{{ route('quote.create', ['service' => $key]) }}" class="tagline mt-6">{{ $s['tagline'] }}<span class="sr-only">: get a quote for {{ \Illuminate\Support\Str::lower($s['title']) }}</span><x-icon name="arrow-right" class="h-4 w-4" /></a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="cut" aria-hidden="true"></div>
    </section>

    {{-- Mission, in the company's own words, beside the company's own photo --}}
    <section class="wrap mt-20 grid items-center gap-10 md:mt-28 lg:grid-cols-[1fr_1.1fr]">
        <figure class="relative" data-reveal>
            <x-photo key="wet-floor" alt="An Allpro cleaner's wet-floor sign with the APS logo on a freshly mopped floor" sizes="(min-width: 1024px) 40vw, 100vw" class="aspect-[4/3] rounded-lg" />
            <figcaption class="photo-caption">Allpro Services photograph.</figcaption>
        </figure>
        <div>
            <x-lanyard code="02" data-reveal>In good hands</x-lanyard>
            <blockquote class="mt-6">
                <p class="text-[clamp(1.4rem,1.1rem+1vw,2rem)] font-bold leading-snug">“{{ allpro('about.mission') }}”</p>
                <footer class="mt-4 text-ink-3">Allpro Services mission statement</footer>
            </blockquote>
            <a href="{{ route('about') }}" class="btn btn-outline mt-8">About Allpro<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>

    {{-- Four offices on a map of Ireland: choose one (or let it cycle) to light up the area it covers. --}}
    <section class="tinted relative isolate mt-20 overflow-hidden bg-deep-900 text-white on-dark md:mt-28" aria-labelledby="regions-title" x-data="irelandMap(@js(array_keys($regions)))">
        <div class="absolute inset-0 -z-10">
            <x-photo key="aps-corridor" alt="" sizes="100vw" class="h-full w-full" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
        <div class="cut top" aria-hidden="true"></div>
        <div class="wrap grid gap-10 pb-24 pt-24 md:pb-32 md:pt-32 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.85fr)] lg:items-center lg:gap-16">
            <div class="mx-auto w-full max-w-[30rem] lg:order-2">
                @include('partials.ireland-map')
                <p class="mt-2 text-center text-xs text-white">Areas are indicative, by county.</p>
            </div>
            <div class="min-w-0">
                <x-lanyard code="03" data-reveal>Four regions</x-lanyard>
                <h2 id="regions-title" class="display-2 mt-4 max-w-[18ch]">Local teams in Galway, Dublin, Limerick and Cork.</h2>
                <p class="lead mt-4 text-white">Quote requests go straight to the sales team for the county the site is in, and staff are recruited close to the sites they work on.</p>
                <ul class="mt-8 grid gap-2.5" aria-label="Regional offices">
                    @foreach ($regions as $key => $r)
                        <li>
                            <button type="button" class="region-btn" :aria-pressed="(active === '{{ $key }}').toString()" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" @click="pick('{{ $key }}')" @mouseenter="pick('{{ $key }}')" @focus="pick('{{ $key }}')">
                                <span class="min-w-0"><span class="block text-lg font-black uppercase tracking-[0.04em]">{{ $r['label'] }}</span><span class="block text-sm text-white">{{ $r['office'] }} · {{ implode(', ', $r['counties']) }}</span></span>
                                <span class="flex-none rounded bg-white px-2 py-1 text-xs font-black tracking-[0.1em] text-[#00597a]">{{ $r['code'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('contact') }}" class="btn btn-on-dark mt-8">Contact details<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
        </div>
        <div class="cut" aria-hidden="true"></div>
    </section>

    {{-- How a quote works: three steps joined by a line that draws across as it comes into view. --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="how-title">
        <x-lanyard code="04" data-reveal>New contracts</x-lanyard>
        <h2 id="how-title" class="display-2 mt-4 max-w-[22ch]">From first call to first shift.</h2>
        <ol class="relative mt-10 grid gap-6 lg:grid-cols-3">
            <span class="steps-line absolute left-6 right-6 top-[3.35rem] hidden h-1 rounded bg-primary lg:block" aria-hidden="true" data-reveal></span>
            @foreach ([
                ['Tell us about the site', 'Which services, the address, the size and the cover you need. Attach floor plans or a tender specification if you have them.'],
                ['Pick a survey date', 'Choose a weekday and a time of day. The sales owner for your region confirms the visit and walks the site with you.'],
                ['Get a costed proposal', 'A quote built around the survey. Once on contract, your site gets its own service-desk link for requests and complaints.'],
            ] as [$t, $d])
                <li class="sheet relative p-6" data-reveal style="--reveal-delay: {{ $loop->index * 140 }}ms">
                    <x-checkpoint :n="$loop->iteration" :light="true" class="bg-surface" />
                    <h3 class="mt-4 text-xl font-black uppercase tracking-[0.01em]">{{ $t }}</h3>
                    <p class="mt-2 text-ink-2">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
        <a href="{{ route('quote.create') }}" class="btn btn-primary mt-8">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
    </section>

    {{-- Contact strip --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="visit-title">
        <div class="grid gap-8 border-t border-line pt-10 md:grid-cols-3">
            <div><h2 id="visit-title" class="display-3">Talk to the helpdesk</h2><p class="mt-2 text-ink-2">Questions about a service, a site or a job: call or email the head office in Oranmore.</p></div>
            <div>
                <p class="kicker text-ink-3">Telephone</p>
                <a href="{{ tel_href(allpro('phone')) }}" class="mt-1 inline-flex min-h-11 items-center text-2xl font-black text-link">{{ format_phone(allpro('phone')) }}</a>
                <p class="kicker mt-3 text-ink-3">Email</p>
                <a href="mailto:{{ allpro('email') }}" class="mt-1 inline-flex min-h-11 items-center text-lg font-bold text-link">{{ allpro('email') }}</a>
            </div>
            <div>
                <p class="kicker text-ink-3">Head office</p>
                <p class="mt-1 text-lg">{{ allpro('address.street') }}<br>{{ allpro('address.town') }}, {{ allpro('address.county') }}</p>
                <a href="{{ allpro('map_url') }}" target="_blank" rel="noopener" class="mt-1 inline-flex min-h-11 items-center gap-1.5 font-bold text-link">Get directions<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens Google Maps)</span></a>
            </div>
        </div>
    </section>
</x-layouts.site>
