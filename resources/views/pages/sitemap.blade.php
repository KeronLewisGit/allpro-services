<x-layouts.site title="Site map" description="Every page on the Allpro Services website.">
    <x-page-header photo="galway" title="Site map" kicker="Site map" code="L-03" lead="Every public page on this website." />
    <section class="wrap mt-12 grid gap-10 md:mt-16 md:grid-cols-3">
        <div><h2 class="font-display text-lg font-bold">Allpro</h2><ul class="mt-3 space-y-1">
            @foreach (['home' => 'Home', 'services' => 'Services', 'about' => 'About Allpro', 'contact' => 'Contact', 'privacy' => 'Privacy notice', 'credits' => 'Photo credits'] as $r => $l)
                <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route($r) }}">{{ $l }}</a></li>
            @endforeach
        </ul></div>
        <div><h2 class="font-display text-lg font-bold">Clients</h2><ul class="mt-3 space-y-1">
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('quote.create') }}">Request a quote or site survey</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('clients') }}">Client service desk</a></li>
            @foreach ((array) allpro('services') as $k => $s)<li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('services') }}#{{ $k }}">{{ $s['title'] }}</a></li>@endforeach
        </ul></div>
        <div><h2 class="font-display text-lg font-bold">Careers</h2><ul class="mt-3 space-y-1">
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.index') }}">All openings</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.apply') }}">Apply</a></li>
            @foreach ($jobs as $j)<li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.show', $j->slug) }}">{{ $j->title }}</a></li>@endforeach
        </ul></div>
    </section>
</x-layouts.site>
