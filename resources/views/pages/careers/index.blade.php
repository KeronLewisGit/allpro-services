@php
    use App\Enums\Region;
    use App\Enums\RoleType;
@endphp
<x-layouts.site title="Careers" description="Cleaning operatives, security officers and supervisors: work with Allpro Services in Galway, Dublin, Limerick and Cork.">
    <x-page-header title="Work with Allpro" kicker="Careers" code="C-01" photo="guard-gate"
        lead="We hire cleaning operatives, security officers and supervisors in all four regions, for early, late and night shifts. Apply for an opening below, or send a general application and we will call when a site near you needs cover." />

    <section class="wrap mt-12 md:mt-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><x-lanyard code="C-01">Openings</x-lanyard><h2 class="display-3 mt-3">{{ $jobs->isEmpty() ? 'No openings match' : $jobs->count().' '.\Illuminate\Support\Str::plural('opening', $jobs->count()).' posted' }}</h2></div>
            <a href="{{ route('careers.apply') }}" class="btn btn-outline">General application<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <nav class="mt-6 space-y-2" aria-label="Filter openings">
            <ul class="tag-row">
                <li><a class="tag" href="{{ route('careers.index', array_filter(['role' => $role?->value])) }}" @if(! $region) aria-current="true" @endif>All regions <span class="count">{{ $all->filter(fn ($j) => ! $role || $j->role_type === $role)->count() }}</span></a></li>
                @foreach (Region::cases() as $r)
                    <li><a class="tag" href="{{ route('careers.index', array_filter(['region' => $r->value, 'role' => $role?->value])) }}" @if($region === $r) aria-current="true" @endif>{{ $r->label() }} <span class="count">{{ $all->filter(fn ($j) => $j->region === $r && (! $role || $j->role_type === $role))->count() }}</span></a></li>
                @endforeach
            </ul>
            <ul class="tag-row">
                <li><a class="tag" href="{{ route('careers.index', array_filter(['region' => $region?->value])) }}" @if(! $role) aria-current="true" @endif>All roles</a></li>
                @foreach (RoleType::cases() as $r)
                    <li><a class="tag" href="{{ route('careers.index', array_filter(['role' => $r->value, 'region' => $region?->value])) }}" @if($role === $r) aria-current="true" @endif><x-icon :name="$r->icon()" class="h-4 w-4" />{{ $r->plural() }}</a></li>
                @endforeach
            </ul>
        </nav>
        <div class="mt-8 grid gap-5 md:grid-cols-2">
            @forelse ($jobs as $job)
                <article class="card-link sheet p-6" data-reveal>
                    <p class="flex items-center justify-between gap-3"><span class="kicker text-ink-3">{{ $job->role_type->label() }}</span><span class="chip">{{ $job->region->code() }}</span></p>
                    <h3 class="display-3 mt-2 text-[1.375rem]"><a class="stretch" href="{{ route('careers.show', $job) }}">{{ $job->title }}</a></h3>
                    <p class="mt-2 text-ink-2">{{ $job->summary }}</p>
                    <p class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-ink-3">
                        <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="h-4 w-4" />{{ $job->location }}</span>
                        @if ($job->hours)<span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4" />{{ $job->hours }}</span>@endif
                        <span class="inline-flex items-center gap-1.5"><x-icon name="briefcase" class="h-4 w-4" />{{ $job->employment_type->label() }}</span>
                        @if ($job->closes_on)<span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="h-4 w-4" />Closes {{ $job->closes_on->format('j M') }}</span>@endif
                    </p>
                    @if ($job->is_demo)<p class="mt-4"><span class="demo-badge">Sample posting</span></p>@endif
                </article>
            @empty
                <p class="panel p-6 text-ink-2 md:col-span-2">Nothing posted for that choice right now. Send a general application and we will keep it on file.</p>
            @endforelse
        </div>
    </section>

    <section class="wrap mt-20">
        <div class="grid gap-8 lg:grid-cols-3">
            @foreach ((array) allpro('careers.points') as $i => $p)
                <div class="flex gap-4"><x-checkpoint :n="$i + 1" :light="true" class="text-primary" /><div><h3 class="font-display text-lg font-bold">{{ $p['title'] }}</h3><p class="mt-1 text-ink-2">{{ $p['text'] }}</p></div></div>
            @endforeach
        </div>
    </section>
</x-layouts.site>
