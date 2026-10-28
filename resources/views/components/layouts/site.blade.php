@props(['title' => null, 'description' => null, 'jsonLd' => [], 'mobileBar' => true, 'ogImage' => null])
@php
    $pageTitle = $title ? $title.' · '.allpro('short_name') : allpro('short_name').' · Contract cleaning, security and facilities management, Ireland';
    $metaDescription = $description ?? allpro('description');
    $phone = allpro('phone');
    $email = allpro('email');
    $address = allpro('address');
    $og = $ogImage ?? allpro('og_photo');
    $nav = [
        ['route' => 'services', 'match' => 'services', 'label' => 'Services'],
        ['route' => 'about', 'match' => 'about', 'label' => 'About'],
        ['route' => 'clients', 'match' => ['clients', 'portal.*'], 'label' => 'Client services'],
        ['route' => 'careers.index', 'match' => 'careers.*', 'label' => 'Careers'],
        ['route' => 'contact', 'match' => 'contact', 'label' => 'Contact'],
    ];
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => allpro('name'),
        'alternateName' => allpro('alternate_names'),
        'url' => route('home'),
        'telephone' => '+'.ie_digits($phone),
        'email' => $email,
        'foundingDate' => (string) allpro('founded'),
        'image' => asset('img/'.allpro('og_photo').'-1920.webp'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $address['street'],
            'addressLocality' => $address['town'],
            'addressRegion' => $address['county'],
            'addressCountry' => 'IE',
        ],
        'areaServed' => 'Ireland',
    ];
@endphp
<!DOCTYPE html>
<html lang="en-IE">
<head>
    @include('partials.head')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if (allpro('preview'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ allpro('short_name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/'.$og.'-1920.webp') }}">
    <meta property="og:locale" content="en_IE">
    <meta name="twitter:card" content="summary_large_image">
    @foreach (array_merge([$organization], $jsonLd) as $ld)
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen {{ $mobileBar ? 'pb-[5.25rem] lg:pb-0' : '' }}" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface focus:px-4 focus:py-3 focus:shadow-md">Skip to content</a>

    @if (allpro('preview'))
        <div class="bg-hivis text-sm text-deep-950">
            <p class="wrap flex items-start gap-2 py-2.5"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" /><span><strong class="font-bold">Concept preview prepared for Allpro Services.</strong> This is not the company's official website. Forms sent here do not reach Allpro.</span></p>
        </div>
    @endif

    <div class="hidden bg-deep-950 text-[0.875rem] text-white/80 lg:block">
        <div class="wrap flex h-10 items-center gap-6">
            <span class="inline-flex items-center gap-2"><x-icon name="map-pin" class="h-4 w-4 text-hivis" />Head office: {{ $address['street'] }}, {{ $address['town'] }}</span>
            <a href="{{ tel_href($phone) }}" class="inline-flex min-h-10 items-center gap-2 hover:text-white"><x-icon name="phone" class="h-4 w-4 text-hivis" />{{ format_phone($phone) }}</a>
            <a href="mailto:{{ $email }}" class="inline-flex min-h-10 items-center gap-2 hover:text-white"><x-icon name="mail" class="h-4 w-4 text-hivis" />{{ $email }}</a>
            <a href="{{ route('clients') }}" class="ml-auto inline-flex min-h-10 items-center gap-2 font-semibold text-white hover:text-hivis"><x-icon name="message" class="h-4 w-4" />Existing client? Log a request</a>
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-line bg-surface/95 backdrop-blur">
        <div class="wrap flex h-[4.5rem] items-center gap-2 min-[400px]:gap-3">
            <a href="{{ route('home') }}" class="mr-auto inline-flex min-h-11 flex-none items-center" aria-label="Allpro Services, home"><x-logo /></a>

            <nav class="hidden items-center lg:flex" aria-label="Main">
                @foreach ($nav as $item)
                    @php($current = request()->routeIs($item['match']))
                    <a href="{{ route($item['route']) }}" @class(['relative whitespace-nowrap px-2.5 py-2.5 text-[0.9375rem] font-semibold transition-colors xl:px-3.5', 'text-ink' => $current, 'text-ink-2 hover:text-ink' => ! $current]) @if($current) aria-current="page" @endif>
                        {{ $item['label'] }}
                        @if ($current)<span class="absolute inset-x-2.5 -bottom-[0.95rem] h-[3px] rounded-full bg-primary xl:inset-x-3.5" aria-hidden="true"></span>@endif
                    </a>
                @endforeach
            </nav>

            <button type="button" onclick="toggleTheme()" class="hidden h-11 w-11 flex-none items-center justify-center rounded-lg text-ink-2 hover:bg-surface-2 hover:text-ink sm:inline-flex" aria-label="Switch light or dark theme">
                <x-icon name="moon" class="h-5 w-5 dark-hide" /><x-icon name="sun" class="hidden h-5 w-5 dark-show" />
            </button>
            <a href="{{ route('quote.create') }}" class="btn btn-primary btn-sm hidden flex-none whitespace-nowrap lg:inline-flex">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            <button type="button" class="inline-flex h-11 w-11 flex-none items-center justify-center rounded-lg ring-1 ring-line-strong lg:hidden" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="Menu">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-show="menu" x-cloak />
            </button>
        </div>
        <div id="mobile-menu" class="border-t border-line bg-surface lg:hidden" x-show="menu" x-cloak x-transition.opacity>
            <nav class="wrap flex flex-col py-3" aria-label="Mobile">
                <a href="{{ route('home') }}" class="rounded px-1 py-3 text-xl font-bold">Home</a>
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="rounded px-1 py-3 text-xl font-bold" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <div class="mt-3 grid gap-3 border-t border-line pt-5 sm:grid-cols-2">
                    <a href="{{ route('quote.create') }}" class="btn btn-primary">Request a quote</a>
                    <a href="{{ tel_href($phone) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($phone) }}</a>
                </div>
                <button type="button" onclick="toggleTheme()" class="mt-3 flex min-h-11 items-center gap-2 rounded px-1 text-left font-semibold text-ink-2"><x-icon name="moon" class="h-5 w-5" />Switch light or dark theme</button>
            </nav>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="site-footer mt-24 bg-deep-900 text-white/75">
        <div class="hivis" aria-hidden="true"></div>
        <div class="siteplan">
            <div class="wrap grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.15fr]">
                <div>
                    <x-logo tone="light" />
                    <p class="mt-5 max-w-xs text-white/75">{{ allpro('footer_line') }}</p>
                    <a href="{{ tel_href($phone) }}" class="font-display mt-6 inline-flex min-h-12 items-center gap-2.5 text-2xl font-bold text-white hover:text-hivis"><x-icon name="phone" class="h-5 w-5" />{{ format_phone($phone) }}</a>
                    <p class="mt-1"><a class="inline-flex min-h-11 items-center hover:text-white" href="mailto:{{ $email }}">{{ $email }}</a></p>
                </div>
                <div>
                    <h2 class="kicker font-sans text-white/60">Services</h2>
                    <ul class="mt-4 space-y-1">
                        @foreach (['facilities', 'cleaning', 'security', 'industrial'] as $k)
                            <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('services') }}#{{ $k }}">{{ allpro('services.'.$k.'.title') }}</a></li>
                        @endforeach
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('services') }}">All services</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="kicker font-sans text-white/60">Work with us</h2>
                    <ul class="mt-4 space-y-1">
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('quote.create') }}">Request a quote or site survey</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('clients') }}">Client service desk</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('careers.index') }}">Careers</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('about') }}">About Allpro</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('contact') }}">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="kicker font-sans text-white/60">Head office</h2>
                    <address class="mt-5 not-italic leading-relaxed">
                        {{ allpro('short_name') }}<br>{{ $address['street'] }}<br>{{ $address['town'] }}, {{ $address['county'] }}
                    </address>
                    <a class="mt-1 inline-flex min-h-11 items-center gap-1.5 hover:text-white" href="{{ allpro('map_url') }}" rel="noopener" target="_blank">Get directions<x-icon name="external" class="h-3.5 w-3.5" /><span class="sr-only">(opens Google Maps)</span></a>
                    <p class="mt-3 text-sm text-white/65">Also in Dublin, Limerick and Cork.</p>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="wrap flex flex-col gap-3 py-6 text-sm text-white/65 md:flex-row md:items-center md:justify-between">
                    <p>© {{ date('Y') }} {{ allpro('name') }}, trading as {{ allpro('trading_as') }}. Registered in Ireland no. {{ allpro('company_number') }}.</p>
                    <p class="flex flex-wrap gap-x-5">
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('privacy') }}">Privacy</a>
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('credits') }}">Photo credits</a>
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('sitemap.page') }}">Site map</a>
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('login') }}">Staff sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    @if ($mobileBar)
        {{-- Thumb-reach actions on phones: call the helpdesk, email, request a quote. --}}
        <nav aria-label="Quick actions" class="fixed inset-x-0 bottom-0 z-30 border-t border-white/10 bg-deep-900/95 px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden">
            <div class="flex gap-2">
                <a href="{{ tel_href($phone) }}" class="btn btn-on-dark flex-1 px-2 text-[0.9375rem]"><x-icon name="phone" class="h-4 w-4" />Call</a>
                <a href="mailto:{{ $email }}" class="btn btn-on-dark flex-1 px-2 text-[0.9375rem]"><x-icon name="mail" class="h-4 w-4" />Email</a>
                <a href="{{ route('quote.create') }}" class="btn btn-hivis flex-[1.5] whitespace-nowrap px-2 text-[0.9375rem]">Get a quote</a>
            </div>
        </nav>
    @endif
</body>
</html>
