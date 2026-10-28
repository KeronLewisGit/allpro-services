<x-layouts.site title="Photo credits" description="Sources and licences for the photographs on this website.">
    <x-page-header photo="cctv" title="Photo credits" kicker="Credits" code="L-02" lead="Three photos come from Allpro's own website. The others come from Wikimedia Commons and show the kind of work, not Allpro staff, sites or clients." />
    <section class="wrap mt-12 md:mt-16">
        <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach (config('photo-credits') as $key => $c)
                <li class="sheet flex flex-col overflow-hidden">
                    <x-photo :key="$key" alt="" sizes="(min-width: 1024px) 400px, 100vw" class="aspect-[16/10] border-b border-line" />
                    <div class="p-5 text-[0.9375rem]">
                        <p class="font-semibold">{{ $c['title'] }}</p>
                        <p class="mt-1 text-ink-2">{{ $c['author'] }} · @if($c['licence_url'])<a class="text-link" href="{{ $c['licence_url'] }}" rel="noopener license" target="_blank">{{ $c['licence'] }}</a>@else{{ $c['licence'] }}@endif</p>
                        <p class="mt-1 text-ink-2"><a class="text-link" href="{{ $c['url'] }}" rel="noopener" target="_blank">{{ $c['source'] }}</a></p>
                        <p class="hint mt-2">{{ $c['changes'] }} Used on: {{ $c['used_for'] }}.</p>
                    </div>
                </li>
            @endforeach
        </ul>
        <h2 class="display-3 mt-14">Map</h2>
        <p class="mt-3 measure text-ink-2">The map of Ireland on the home page is adapted from <a class="text-link" href="https://commons.wikimedia.org/wiki/File:Ireland_trad_counties_blank.svg" rel="noopener" target="_blank">“Ireland trad counties blank.svg”</a> by Draceane, Wikimedia Commons, <a class="text-link" href="https://creativecommons.org/licenses/by-sa/3.0" rel="noopener license" target="_blank">CC BY-SA 3.0</a>. Changes: outlines simplified and counties grouped into Allpro's four regions. The adapted map is shared under the same licence.</p>
    </section>
</x-layouts.site>
