@php($about = (array) allpro('about'))
<x-layouts.site title="About Allpro" description="Allpro Services was established in 2002 as a contract cleaning company and is now a facilities management provider with offices in Galway, Dublin, Limerick and Cork.">
    <x-page-header title="Looking after Irish buildings since 2002" kicker="About Allpro" code="A-01" photo="business-park" position="center 40%"
        :lead="$about['intro']" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="prose-copy measure min-w-0 text-[1.0625rem]">
            <h2 class="!mt-0">Our mission</h2>
            <p>{{ $about['mission'] }}</p>
            <h2>Our vision</h2>
            <p>{{ $about['vision'] }}</p>
            <p class="hint">In the company's own words, from {{ $about['source'] }}.</p>
            <h2>What that looks like day to day</h2>
            <p>Most clients start with one service, usually contract cleaning or security, and add others as the relationship grows. A facilities management contract brings cleaning, security, washroom services, consumables, landscaping and pest control together with one point of contact.</p>
        </div>
        <aside class="space-y-6">
            <x-sitecard :light="true" :cols="1" head="Company" :fields="['Registered name' => allpro('name'), 'Trading as' => allpro('trading_as'), 'Established' => (string) allpro('founded'), 'Company number' => allpro('company_number'), 'Head office' => allpro('address.street').', '.allpro('address.town')]" />
            <x-photo key="wet-floor" alt="An Allpro wet-floor sign on a freshly cleaned floor" sizes="20rem" class="aspect-[4/3] rounded-2xl" />
            <p class="photo-caption -mt-4">Allpro Services photograph.</p>
        </aside>
    </section>

    <section class="wrap mt-20" aria-labelledby="offices-title">
        <x-lanyard code="A-02">Offices and depots</x-lanyard>
        <h2 id="offices-title" class="display-3 mt-4">Four regional bases</h2>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ((array) allpro('regions') as $r)
                <div class="panel p-5">
                    <p class="flex items-center justify-between"><span class="font-display text-xl font-bold">{{ $r['label'] }}</span><span class="chip">{{ $r['code'] }}</span></p>
                    <p class="mt-1 text-ink-2">{{ $r['office'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="wrap mt-16 flex flex-wrap gap-3">
        <a href="{{ route('services') }}" class="btn btn-primary">Our services<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        <a href="{{ route('careers.index') }}" class="btn btn-outline">Careers</a>
        <a href="{{ route('contact') }}" class="btn btn-outline">Contact us</a>
    </section>
</x-layouts.site>
